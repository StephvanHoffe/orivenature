<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use UnitEnum;

/** Onderhoud zonder SSH: database bijwerken na een update en caches legen. */
class Maintenance extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Instellingen';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Onderhoud';

    protected static ?string $title = 'Onderhoud';

    protected static ?string $slug = 'instellingen/onderhoud';

    protected static ?int $navigationSort = 40;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === 'owner';
    }

    public function content(Schema $schema): Schema
    {
        Artisan::call('migrate:status', ['--pending' => true]);
        $pending = substr_count(Artisan::output(), 'Pending');
        $mediaSize = collect(File::exists(public_path('media')) ? File::allFiles(public_path('media')) : [])->sum(fn ($f) => $f->getSize());

        return $schema->components([
            Section::make('Database')->schema([
                Text::make($pending ? "Er staan {$pending} database-updates klaar. Voer ze uit na het installeren van een nieuwe versie." : 'De database is up-to-date.'),
                Actions::make([
                    Action::make('migrate')->label('Database bijwerken')->icon(Heroicon::OutlinedCircleStack)->requiresConfirmation()
                        ->modalDescription('Maak bij voorkeur eerst een back-up van de database via DirectAdmin.')
                        ->action(function () {
                            Artisan::call('migrate', ['--force' => true]);
                            Notification::make()->success()->title('Database bijgewerkt')->body(trim(Artisan::output()) ?: null)->send();
                        }),
                ]),
            ]),
            Section::make('Cache')->schema([
                Text::make(new HtmlString('<div class="ob-help">Leeg de cache als wijzigingen in het .env-bestand of na een update niet zichtbaar zijn. Verkleinde foto\'s ('
                    .number_format($mediaSize / 1048576, 1, ',', '.').' MB) worden opnieuw gemaakt wanneer ze nodig zijn.</div>')),
                Actions::make([
                    Action::make('clear')->label('Cache legen')->icon(Heroicon::OutlinedArrowPath)->color('gray')
                        ->action(function () {
                            Artisan::call('optimize:clear');
                            settings()->flush();
                            Notification::make()->success()->title('Cache geleegd')->send();
                        }),
                    Action::make('media')->label('Verkleinde foto\'s opnieuw maken')->icon(Heroicon::OutlinedPhoto)->color('gray')->requiresConfirmation()
                        ->action(function () {
                            File::cleanDirectory(public_path('media'));
                            File::put(public_path('media/.gitignore'), "*\n!.gitignore\n");
                            Notification::make()->success()->title('Verkleinde foto\'s verwijderd')->body('Ze worden automatisch opnieuw gemaakt.')->send();
                        }),
                ]),
            ]),
            Section::make('Systeem')->schema([
                Text::make('PHP '.PHP_VERSION.' · Laravel '.app()->version().' · '.(config('app.debug') ? 'foutmodus AAN (zet APP_DEBUG=false)' : 'productiemodus')),
            ]),
        ]);
    }
}
