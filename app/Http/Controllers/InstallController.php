<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Import\ShopifyImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * Eenmalige installatie op hosting zonder SSH: database aanmaken, eigenaar-account
 * en (optioneel) de Shopify-gegevens overzetten. Werkt alleen met INSTALL_TOKEN in .env
 * en zolang er nog geen beheerders zijn.
 */
class InstallController extends Controller
{
    private function available(): bool
    {
        if (blank(config('app.install_token'))) {
            return false;
        }
        try {
            return ! Schema::hasTable('users') || User::count() === 0;
        } catch (\Throwable) {
            return true;
        }
    }

    private function checks(): array
    {
        $db = null;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $db = $e->getMessage();
        }
        $checks = [
            'PHP 8.3 of hoger (nu '.PHP_VERSION.')' => version_compare(PHP_VERSION, '8.3.0', '>='),
        ];
        foreach (['pdo_mysql', 'mbstring', 'intl', 'gd', 'fileinfo', 'curl', 'openssl', 'dom', 'xml', 'zip'] as $ext) {
            $checks["PHP-extensie {$ext}"] = extension_loaded($ext);
        }
        foreach ([storage_path(), storage_path('framework/cache'), storage_path('framework/sessions'), storage_path('framework/views'), storage_path('logs'), base_path('bootstrap/cache'), public_path('uploads'), public_path('media')] as $dir) {
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $checks['Schrijfbaar: '.str_replace(dirname(base_path()).'/', '', $dir)] = is_writable($dir);
        }
        $checks['Databaseverbinding'.($db ? ' ('.$db.')' : '')] = $db === null;
        $checks['APP_URL ingesteld ('.config('app.url').')'] = ! str_contains((string) config('app.url'), 'localhost');

        return $checks;
    }

    public function show()
    {
        abort_unless($this->available(), 404);

        return view('install', ['checks' => $this->checks(), 'done' => false, 'log' => []]);
    }

    public function run(Request $request)
    {
        abort_unless($this->available(), 404);
        // Zonder sessie: fouten direct in de pagina tonen
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:10'],
            'import' => ['nullable', 'boolean'],
        ], [], ['name' => 'naam', 'password' => 'wachtwoord', 'token' => 'installatiecode']);
        if ($validator->fails() || ! hash_equals((string) config('app.install_token'), (string) $request->input('token'))) {
            $error = $validator->fails() ? $validator->errors()->first() : 'De installatiecode klopt niet.';

            return response()->view('install', ['checks' => $this->checks(), 'done' => false, 'log' => [], 'error' => $error], 422);
        }
        $data = $validator->validated();
        @set_time_limit(900);

        $log = [];
        Artisan::call('migrate', ['--force' => true]);
        $log[] = 'Database aangemaakt.';
        User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => $data['password'], 'role' => 'owner', 'is_active' => true]);
        $log[] = 'Eigenaar-account aangemaakt voor '.$data['email'].'.';

        if (! empty($data['import'])) {
            try {
                (new ShopifyImporter('https://www.orivenature.com', function ($m) use (&$log) {
                    $log[] = trim($m);
                }))->importAll();
            } catch (\Throwable $e) {
                report($e);
                $log[] = 'Overzetten gestopt: '.$e->getMessage().' Je kunt dit later opnieuw doen via Beheer > Instellingen > Overzetten uit Shopify.';
            }
        } else {
            (new ShopifyImporter)->setupStoreDefaults();
        }

        return view('install', ['checks' => [], 'done' => true, 'log' => $log]);
    }
}
