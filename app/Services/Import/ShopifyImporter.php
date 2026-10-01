<?php

namespace App\Services\Import;

use App\Models\Article;
use App\Models\Collection;
use App\Models\Discount;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Redirect;
use App\Models\ShippingZone;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Haalt producten, collecties, blog en pagina's op uit de openbare Shopify-winkel.
 * Producten met een inhoud in de titel ("Matcha Essence 50 gram" + "… 100 gram")
 * worden samengevoegd tot één product met varianten; oude adressen worden doorverwezen.
 */
class ShopifyImporter
{
    /** Extra presentatie per productsoort (op basis van de handle). */
    private const PRESENTATION = [
        'matcha-essence' => ['chip' => 'ceremonieel · Japan', 'tone' => '#c9d6a9', 'short' => '100% biologische ceremoniële matcha uit Japan. Perfect voor matcha thee, lattes en dagelijkse recepten.'],
        'ube-essence' => ['chip' => 'Filipijnen', 'tone' => '#ceb8ca', 'short' => '100% biologisch ube poeder uit de Filipijnen. Perfect voor lattes, desserts en authentieke Aziatische recepten.'],
        'dragon-fruit-essence' => ['chip' => 'nieuw', 'chip_highlight' => true, 'tone' => '#e4d8e2', 'short' => '100% biologisch dragon fruit poeder met een levendige roze kleur en subtiel tropische smaak. Perfect voor lattes, smoothies, bowls en dagelijkse recepten.'],
        'orive-ritual-box' => ['tone' => '#e4ead2', 'tax' => 21],
    ];

    public const HOME_MEDIA = [
        'home-1.png' => 'files/home-1.png?v=1788899086',
        'pic-1.png' => 'files/pic-1.png?v=1788897627',
        'pic-2.jpg' => 'files/pic-2.jpg?v=1788897667',
        'pic-3.png' => 'files/pic-3.png?v=1788897651',
        'pic-4.jpg' => 'files/pic-4.jpg?v=1788897705',
        'WhatsApp_Image_2026-09-08_at_21.44.20_1.jpg' => 'files/WhatsApp_Image_2026-09-08_at_21.44.20_1.jpg?v=1788897773',
        '004.jpg' => 'files/004.jpg?v=1782336469',
        '011.jpg' => 'files/011.jpg?v=1782857346',
        '002.png' => 'files/002.png?v=1789253769',
        'box-003.png' => 'files/box-003.png?v=1789163914',
        'farmer_2.jpg' => 'files/farmer_2.jpg?v=1780440802',
    ];

    public const PAGES = ['over-ons', 'contact', 'store-locator', 'vacatures', 'word-retailer', 'veelgestelde-vragen', 'algemene-voorwaarden', 'privacy-policy'];

    /** @var callable|null */
    private $log;

    public function __construct(private string $baseUrl = 'https://www.orivenature.com', ?callable $log = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->log = $log;
    }

    private function say(string $message): void
    {
        if ($this->log) {
            ($this->log)($message);
        }
    }

    private function http()
    {
        return Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; OriveImporter/1.0)', 'Accept-Language' => 'nl-NL,nl;q=0.9'])
            ->timeout(60)->retry(4, 2000, throw: false);
    }

    private function getJson(string $path): array
    {
        $response = $this->http()->get($this->baseUrl.$path);
        if (! $response->ok()) {
            throw new \RuntimeException("Ophalen van {$path} mislukt ({$response->status()})");
        }

        return (array) $response->json();
    }

    /** Downloadt een bestand naar public/uploads/{dir}/ en geeft het pad terug (of null). */
    public function download(string $url, string $dir, ?string $name = null): ?string
    {
        $url = str_starts_with($url, '//') ? 'https:'.$url : $url;
        $name ??= basename(parse_url($url, PHP_URL_PATH));
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        $path = trim($dir, '/').'/'.$name;
        if (Storage::disk('public')->exists($path)) {
            return $path;
        }
        $response = $this->http()->get($url);
        if (! $response->ok()) {
            $this->say("  ! kon {$url} niet downloaden ({$response->status()})");

            return null;
        }
        Storage::disk('public')->put($path, $response->body());

        return $path;
    }

    public function importAll(): void
    {
        $this->importProducts();
        $this->importCollections();
        $this->importBlog();
        $this->importPages();
        $this->importHomeMedia();
        $this->setupStoreDefaults();
    }

    public function importProducts(?array $products = null): void
    {
        if ($products === null) {
            $products = [];
            for ($page = 1; $page <= 20; $page++) {
                $batch = $this->getJson("/products.json?limit=250&page={$page}")['products'] ?? [];
                if (! $batch) {
                    break;
                }
                $products = array_merge($products, $batch);
            }
        }
        $this->say('Producten gevonden: '.count($products));

        // Groeperen: "Matcha Essence 50 gram" + "Matcha Essence 100 gram" → één product
        $groups = [];
        foreach ($products as $p) {
            if (preg_match('/^(.*?)\s+(\d+)\s*(gram|gr|g)\.?$/i', trim($p['title']), $m)) {
                $groups[Str::slug($m[1])]['title'] = $m[1];
                $groups[Str::slug($m[1])]['items'][] = ['size' => (int) $m[2], 'product' => $p];
            } else {
                $groups[$p['handle']] = ['title' => $p['title'], 'items' => [['size' => null, 'product' => $p]]];
            }
        }

        $position = 0;
        foreach ($groups as $handle => $group) {
            usort($group['items'], fn ($a, $b) => ($a['size'] ?? 0) <=> ($b['size'] ?? 0));
            DB::transaction(function () use ($handle, $group, &$position) {
                $first = $group['items'][0]['product'];
                $preset = self::PRESENTATION[$handle] ?? [];
                $product = Product::updateOrCreate(['handle' => $handle], [
                    'title' => $group['title'],
                    'description' => $this->cleanHtml((string) ($first['body_html'] ?? '')),
                    'status' => 'active',
                    'product_type' => $first['product_type'] ?: null,
                    'vendor' => $first['vendor'] ?: null,
                    'tags' => $first['tags'] ?: null,
                    'option_names' => count($group['items']) > 1 ? ['Inhoud'] : null,
                    'tax_rate' => $preset['tax'] ?? 9,
                    'short_description' => $preset['short'] ?? null,
                    'chip' => $preset['chip'] ?? null,
                    'chip_highlight' => $preset['chip_highlight'] ?? false,
                    'tone' => $preset['tone'] ?? null,
                    'position' => $position++,
                    'published_at' => $first['published_at'] ?? now(),
                    'shopify_id' => $first['id'],
                ]);

                $imagePos = 0;
                foreach ($group['items'] as $i => $item) {
                    $sp = $item['product'];
                    $variantImageId = null;
                    foreach ($sp['images'] ?? [] as $j => $img) {
                        $path = $this->download($img['src'], 'products');
                        if (! $path) {
                            continue;
                        }
                        $image = ProductImage::firstOrCreate(['product_id' => $product->id, 'path' => $path], ['alt' => $img['alt'] ?? $sp['title'], 'position' => $imagePos++]);
                        if ($j === 0) {
                            $variantImageId = $image->id;
                        }
                    }
                    foreach ($sp['variants'] as $k => $v) {
                        $title = $item['size'] ? $item['size'].' gram' : ($v['title'] === 'Default Title' ? 'Standaard' : $v['title']);
                        ProductVariant::updateOrCreate(['shopify_id' => $v['id']], [
                            'product_id' => $product->id,
                            'title' => $title,
                            'option1' => $item['size'] ? $title : $v['option1'],
                            'sku' => $v['sku'] ?: null,
                            'price' => to_cents($v['price']),
                            'compare_at_price' => ! empty($v['compare_at_price']) ? to_cents($v['compare_at_price']) : null,
                            'weight_grams' => (int) ($v['grams'] ?? 0),
                            'track_stock' => false,
                            'stock' => 0,
                            'image_id' => $variantImageId,
                            // Op de oude site waren de kleine verpakkingen de bestsellers
                            'is_bestseller' => $item['size'] !== null && $i === 0,
                            'position' => $i * 10 + $k,
                        ]);
                    }
                    if ($sp['handle'] !== $handle) {
                        $variantId = ProductVariant::where('shopify_id', $sp['variants'][0]['id'])->value('id');
                        Redirect::updateOrCreate(['from_path' => '/products/'.$sp['handle']], ['to_path' => '/products/'.$handle.'?variant='.$variantId]);
                    }
                }
                // Afbeeldingen netjes ordenen: verpakkingen (voorkant) eerst
                $this->sortImages($product);
                $this->say("  ✓ {$product->title} (".$product->variants()->count().' varianten)');
            });
        }
    }

    private function sortImages(Product $product): void
    {
        $variantImages = $product->variants()->pluck('image_id')->filter()->unique()->values()->all();
        $others = $product->images()->whereNotIn('id', $variantImages)->pluck('id')->all();
        foreach (array_merge($variantImages, $others) as $i => $id) {
            ProductImage::whereKey($id)->update(['position' => $i]);
        }
    }

    public function importCollections(): void
    {
        $collections = $this->getJson('/collections.json?limit=250')['collections'] ?? [];
        foreach ($collections as $c) {
            $image = ! empty($c['image']['src']) ? $this->download($c['image']['src'], 'collections') : null;
            $collection = Collection::updateOrCreate(['handle' => $c['handle']], [
                'title' => $c['title'],
                'description' => $this->cleanHtml((string) ($c['body_html'] ?? '')) ?: null,
                'image' => $image,
                'is_visible' => true,
                'shopify_id' => $c['id'],
            ]);
            $listed = collect($this->getJson("/collections/{$c['handle']}/products.json?limit=250")['products'] ?? []);
            // Samengevoegde producten zitten onder de Shopify-id van één van hun varianten
            $productIds = Product::whereIn('shopify_id', $listed->pluck('id'))->pluck('id')
                ->merge(ProductVariant::whereIn('shopify_id', $listed->pluck('variants')->flatten(1)->pluck('id'))->pluck('product_id'))
                ->unique()->values();
            $collection->products()->sync($productIds->mapWithKeys(fn ($id, $i) => [$id => ['position' => $i]])->all());
            $this->say("  ✓ collectie {$collection->title} ({$productIds->count()} producten)");
        }
    }

    public function importBlog(string $blog = 'news'): void
    {
        $response = $this->http()->get("{$this->baseUrl}/blogs/{$blog}.atom");
        if (! $response->ok()) {
            $this->say('  ! blog niet gevonden');

            return;
        }
        $xml = simplexml_load_string($response->body());
        foreach ($xml->entry as $entry) {
            $url = (string) $entry->link['href'];
            $handle = basename(parse_url($url, PHP_URL_PATH));
            $html = $this->cleanHtml((string) $entry->content);
            $image = $this->ogImage($url);
            $article = Article::updateOrCreate(['blog' => $blog, 'handle' => $handle], [
                'title' => (string) $entry->title,
                'body' => $this->localizeImages($html, 'articles'),
                'excerpt' => Str::words(trim(html_entity_decode(strip_tags($html))), 40),
                'image' => $image ? $this->download($image, 'articles') : null,
                'author' => (string) ($entry->author->name ?? '') ?: null,
                'tags' => collect($entry->category)->map(fn ($c) => (string) $c['term'])->filter()->values()->all() ?: null,
                'is_published' => true,
                'published_at' => (string) $entry->published,
            ]);
            $this->say("  ✓ blog: {$article->title}");
        }
    }

    private function ogImage(string $url): ?string
    {
        $html = $this->http()->get($url)->body();
        if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $m)) {
            return html_entity_decode($m[1]);
        }

        return null;
    }

    public function importPages(array $handles = self::PAGES): void
    {
        foreach ($handles as $handle) {
            $response = $this->http()->get("{$this->baseUrl}/pages/{$handle}");
            if (! $response->ok()) {
                $this->say("  ! pagina {$handle} niet gevonden");

                continue;
            }
            [$title, $body] = $this->extractPage($response->body());
            Page::updateOrCreate(['handle' => $handle], [
                'title' => $title ?: Str::headline($handle),
                'body' => $this->localizeImages($body, 'pages'),
                'is_published' => true,
                'template' => match ($handle) {
                    'contact' => 'contact',
                    'word-retailer' => 'wholesale',
                    default => 'default',
                },
            ]);
            $this->say("  ✓ pagina {$handle}");
        }
    }

    /** Zet een opgebouwde Shopify-pagina om in eenvoudige, bewerkbare HTML (tekst + afbeeldingen in leesvolgorde). */
    public function extractPage(string $html): array
    {
        $title = preg_match('/<title>(.*?)<\/title>/s', $html, $m) ? trim(html_entity_decode(strip_tags(explode('–', $m[1])[0]))) : null;
        $start = strpos($html, '<main');
        $end = strpos($html, '</main>');
        if ($start === false || $end === false) {
            return [$title, ''];
        }
        $main = substr($html, $start, $end - $start + 7);
        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>'.$main);
        libxml_clear_errors();
        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//script|//style|//svg|//form|//noscript|//template|//nav[contains(@class,"breadcrumbs")]|//*[contains(@class,"sr-only")]|//*[contains(@class,"breadcrumbs")]|//button') as $node) {
            $node->parentNode?->removeChild($node);
        }
        $out = [];
        $seen = [];
        $walk = function (DOMNode $node) use (&$walk, &$out, &$seen) {
            foreach ($node->childNodes as $child) {
                if (! $child instanceof DOMElement) {
                    continue;
                }
                $tag = strtolower($child->tagName);
                $class = ' '.$child->getAttribute('class').' ';
                $text = trim(preg_replace('/\s+/', ' ', $child->textContent));
                if ($tag === 'img') {
                    $src = $child->getAttribute('src');
                    if ($src && ! isset($seen['img:'.$src])) {
                        $seen['img:'.$src] = true;
                        $out[] = '<p><img src="'.e(str_replace('&amp;', '&', $src)).'" alt="'.e($child->getAttribute('alt')).'"></p>';
                    }

                    continue;
                }
                if ($tag === 'details') {
                    $q = $child->getElementsByTagName('summary')->item(0);
                    $question = $q ? trim($q->textContent) : '';
                    if ($q) {
                        $q->parentNode->removeChild($q);
                    }
                    $answer = trim(preg_replace('/\s+/', ' ', $child->textContent));
                    if ($question && ! isset($seen[$question])) {
                        $seen[$question] = true;
                        $out[] = '<h3>'.e($question).'</h3><p>'.e($answer).'</p>';
                    }

                    continue;
                }
                $isHeading = in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) || preg_match('/ h[1-4] /', $class);
                if ($isHeading && $text !== '') {
                    if (! isset($seen[$text])) {
                        $seen[$text] = true;
                        $level = preg_match('/ h([1-2]) /', $class) || in_array($tag, ['h1', 'h2'], true) ? 'h2' : 'h3';
                        $out[] = "<{$level}>".$this->inline($child)."</{$level}>";
                    }

                    continue;
                }
                if (in_array($tag, ['p', 'ul', 'ol', 'blockquote', 'table'], true) && $text !== '') {
                    if (! isset($seen[$text])) {
                        $seen[$text] = true;
                        $out[] = $tag === 'p' ? '<p>'.$this->inline($child).'</p>' : $child->ownerDocument->saveHTML($child);
                    }

                    continue;
                }
                $walk($child);
            }
        };
        $walk($doc->documentElement);

        return [$title, $this->cleanHtml(implode("\n", $out))];
    }

    private function inline(DOMElement $el): string
    {
        $html = '';
        foreach ($el->childNodes as $c) {
            $html .= $el->ownerDocument->saveHTML($c);
        }

        return trim(strip_tags($html, '<a><strong><b><em><i><br>'));
    }

    /** Verwijdert Shopify-/editor-klassen en lege elementen. */
    public function cleanHtml(string $html): string
    {
        $html = preg_replace('/\s(class|style|id|dir|data-[a-z-]+)="[^"]*"/i', '', $html);
        $html = preg_replace('/<meta[^>]*>/i', '', $html);
        $html = preg_replace('/<span>(.*?)<\/span>/s', '$1', $html);
        $html = preg_replace('/<(b|strong|em|i)>\s*<\/\1>/i', '', $html);
        $html = preg_replace('/<p>\s*(<br\s*\/?>)?\s*<\/p>/i', '', $html);
        // Labels van Shopify-apps (zoals de store locator) die zonder de app niets betekenen
        $html = preg_replace('/<p>(Radius|Tags|Countries|Search|Zoeken)<\/p>\s*/', '', $html);
        $html = str_replace(['https://www.orivenature.com/', 'http://www.orivenature.com/'], '/', $html);

        return trim($html);
    }

    /** Afbeeldingen in HTML lokaal opslaan, zodat ze blijven werken als de Shopify-winkel weg is. */
    private function localizeImages(string $html, string $dir): string
    {
        return preg_replace_callback('/<img([^>]*?)src="([^"]+)"/i', function ($m) use ($dir) {
            $src = html_entity_decode($m[2]);
            if (! str_contains($src, 'cdn.shopify.com') && ! str_contains($src, '/cdn/shop/')) {
                return $m[0];
            }
            $path = $this->download(preg_replace('/([?&])width=\d+/', '$1width=1600', $src), $dir);

            return $path ? '<img'.$m[1].'src="/uploads/'.$path.'"' : $m[0];
        }, $html);
    }

    public function importHomeMedia(): void
    {
        foreach (self::HOME_MEDIA as $name => $path) {
            $this->download('https://cdn.shopify.com/s/files/1/1059/6856/6611/'.$path, 'home', $name);
        }
        $this->download('https://www.orivenature.com/cdn/shop/videos/c/vp/b2bae134bb7e40d1b2bbbad381c4c6e0/b2bae134bb7e40d1b2bbbad381c4c6e0.HD-720p-4.5Mbps-93841317.mp4?v=0', 'home', 'dragonfruit.mp4');
        $this->say('  ✓ afbeeldingen en video van de startpagina');
    }

    /** Menu's, verzending en de welkomstkorting van de nieuwsbrief. */
    public function setupStoreDefaults(): void
    {
        $menus = [
            'main-menu' => ['Hoofdmenu', [
                ['Dragon Fruit', '/collections/dragon-fruit', 'nieuw'], ['Matcha', '/collections/matcha-essence'], ['Ube', '/collections/ube-essence'],
                ['Tools', '/collections/tools'], ['Store locator', '/pages/store-locator'], ['Over ons', '/pages/over-ons'], ['Contact', '/pages/contact'],
            ]],
            'ons-bedrijf' => ['Ons bedrijf', [
                ['Over ons', '/pages/over-ons'], ['Lees onze reviews', 'https://nl.trustpilot.com/review/orivenature.com'], ['Vacatures', '/pages/vacatures'],
                ['Word Retailer', '/pages/word-retailer'], ['Store Locator', '/pages/store-locator'],
            ]],
            'klantenservice' => ['Klantenservice', [
                ['Contact', '/pages/contact'], ['Veelgestelde vragen', '/pages/veelgestelde-vragen'], ['Algemene voorwaarden', '/pages/algemene-voorwaarden'],
                ['Privacy Policy', '/pages/privacy-policy'], ['Blog', '/blogs/news'], ['Whatsapp', 'https://wa.me/31613132968'],
            ]],
            'ons-assortiment' => ['Ons assortiment', [
                ['Matcha', '/collections/matcha-essence'], ['Ube', '/collections/ube-essence'], ['Dragon Fruit', '/collections/dragon-fruit'], ['Tools', '/collections/tools'],
            ]],
        ];
        foreach ($menus as $handle => [$name, $items]) {
            $menu = Menu::firstOrCreate(['handle' => $handle], ['name' => $name]);
            if ($menu->allItems()->exists()) {
                continue;
            }
            foreach ($items as $i => $item) {
                $menu->allItems()->create(['title' => $item[0], 'url' => $item[1], 'badge' => $item[2] ?? null, 'position' => $i]);
            }
        }

        if (! ShippingZone::exists()) {
            $nl = ShippingZone::create(['name' => 'Nederland en België', 'countries' => ['NL', 'BE'], 'position' => 0]);
            $nl->rates()->create(['name' => 'Gratis verzending', 'description' => 'Voor 22:00 besteld, morgen in huis', 'price' => 0]);
            $eu = ShippingZone::create(['name' => 'Europa', 'countries' => ['DE', 'FR', 'LU', 'AT', 'DK', 'ES', 'IT', 'PT', 'IE', 'SE', 'FI', 'PL', 'CZ'], 'position' => 1]);
            // Controleer dit tarief in /beheer > Instellingen > Verzending
            $eu->rates()->create(['name' => 'Verzending Europa', 'description' => '2-5 werkdagen', 'price' => 1295]);
        }

        Discount::firstOrCreate(['code' => 'WELKOM10'], [
            'title' => 'Welkomstkorting nieuwsbrief',
            'type' => 'percentage',
            'value' => 10,
            'once_per_customer' => true,
            'is_active' => true,
        ]);
    }
}
