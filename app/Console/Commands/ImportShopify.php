<?php

namespace App\Console\Commands;

use App\Services\Import\ShopifyCsvImporter;
use App\Services\Import\ShopifyImporter;
use Illuminate\Console\Command;

class ImportShopify extends Command
{
    protected $signature = 'orive:import-shopify
        {--url=https://www.orivenature.com : Adres van de Shopify-winkel}
        {--only= : Alleen een onderdeel: products, collections, blog, pages, media, defaults}
        {--customers= : Pad naar de klanten-CSV uit Shopify}
        {--orders= : Pad naar de bestellingen-CSV uit Shopify}
        {--award-points : Spaarpunten toekennen voor geïmporteerde, betaalde bestellingen}';

    protected $description = 'Zet producten, collecties, blog, pagina\'s, klanten en bestellingen over uit Shopify';

    public function handle(): int
    {
        $importer = new ShopifyImporter($this->option('url'), fn ($m) => $this->line($m));
        $csv = new ShopifyCsvImporter;

        if ($path = $this->option('customers')) {
            $stats = $csv->importCustomers($path);
            $this->info("Klanten: {$stats['created']} nieuw, {$stats['updated']} bijgewerkt, {$stats['skipped']} overgeslagen");
        }
        if ($path = $this->option('orders')) {
            $stats = $csv->importOrders($path, (bool) $this->option('award-points'));
            $this->info("Bestellingen: {$stats['created']} geïmporteerd ({$stats['items']} regels), {$stats['skipped']} overgeslagen");
        }
        if ($this->option('customers') || $this->option('orders')) {
            return self::SUCCESS;
        }

        $steps = [
            'products' => fn () => $importer->importProducts(),
            'collections' => fn () => $importer->importCollections(),
            'blog' => fn () => $importer->importBlog(),
            'pages' => fn () => $importer->importPages(),
            'media' => fn () => $importer->importHomeMedia(),
            'defaults' => fn () => $importer->setupStoreDefaults(),
        ];
        $only = $this->option('only');
        foreach ($steps as $name => $step) {
            if ($only && $only !== $name) {
                continue;
            }
            $this->info(ucfirst($name).'…');
            $step();
        }
        $this->info('Klaar.');

        return self::SUCCESS;
    }
}
