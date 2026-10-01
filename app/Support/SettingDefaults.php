<?php

namespace App\Support;

/**
 * Standaardwaarden voor alle instellingen. Alles is aan te passen in /beheer;
 * wat daar wordt opgeslagen overschrijft deze waarden.
 */
class SettingDefaults
{
    public static function all(): array
    {
        return [
            'store' => [
                'name' => 'Orivé',
                'legal_name' => 'Orivé',
                'email' => 'info@orivenature.com',
                'phone' => null,
                'whatsapp' => '31613132968',
                'address' => "Stavorenweg 8\n2803PT Gouda",
                'kvk' => '89265564',
                'vat_number' => 'NL864928270B01',
                'logo' => null,
                'favicon' => null,
                'announcement_1' => 'Voor 22:00 besteld, morgen in huis',
                'announcement_2' => 'Gratis verzending in (NL/BE)',
                'cutoff_hour' => 22,
                'countdown' => true,
                'social' => [
                    'facebook' => 'https://www.facebook.com/profile.php?id=61591551690093',
                    'instagram' => 'https://www.instagram.com/orivenature',
                    'tiktok' => 'https://www.tiktok.com/@orivenature',
                    'snapchat' => 'https://snapchat.com/t/GYXqBklg',
                ],
                'reviews_url' => 'https://nl.trustpilot.com/review/orivenature.com',
                'whatsapp_bubble' => true,
                'whatsapp_tip' => 'Hulp nodig? App ons',
                'nav_highlight' => 'Dragon Fruit',
                'nav_highlight_label' => 'nieuw',
            ],
            'seo' => [
                'title' => 'Orivé: Aziatische biologische specialiteiten | Matcha, Ube & Meer',
                'description' => 'Orivé brengt de essentie van Azië naar Nederland. Onze biologische essences worden zorgvuldig geselecteerd bij boeren in Azië, families die hun vakmanschap al generaties lang doorgeven. De smaak, de geur, de kwaliteit: puur zoals de natuur het bedoeld heeft, zonder kunstmatige toevoegingen.',
                'og_image' => 'home/home-1.png',
            ],
            'products' => [
                'badge' => 'Gekeurd in Europa',
                'bestseller_label' => 'Best seller',
                'save_text' => 'voordeliger: bespaar [amount] t.o.v. 2× [size]',
                'perks' => [
                    ['icon' => 'truck', 'text' => 'Gratis verzending in (NL/BE)'],
                    ['icon' => 'clock', 'text' => 'Voor 22:00 besteld, morgen in huis'],
                    ['icon' => 'leaf', 'text' => '100% Biologisch Gecertificeerd'],
                    ['icon' => 'check', 'text' => 'Gekeurd in Europa'],
                ],
                'low_stock_threshold' => 10,
            ],
            'cart' => [
                'perks' => ['Gratis verzending in (NL/BE)', 'Voor 22:00 besteld, morgen in huis'],
                'note' => 'De verzendkosten worden berekend aan de checkout op basis van jouw locatie. Gratis verzending geldt voor bestellingen binnen Nederland en België.',
                'upsells' => [
                    ['product' => 'orive-ritual-box', 'label' => 'Maak het ritueel compleet'],
                    ['product' => 'dragon-fruit-essence', 'label' => 'Ontdek ook'],
                ],
                'open_on_add' => true,
            ],
            'checkout' => [
                'require_phone' => false,
                'newsletter_default' => false,
                'order_note' => true,
                'terms_page' => 'algemene-voorwaarden',
                'privacy_page' => 'privacy-policy',
            ],
            'orders' => [
                'start_number' => 1001,
            ],
            'payments' => [
                'mollie_key' => config('services.mollie.key'),
                // Zonder Mollie-sleutel kan de winkel een testbetaling simuleren (alleen voor testen!)
                'test_gateway' => ! app()->isProduction(),
            ],
            'loyalty' => [
                'enabled' => true,
                'name' => 'Orivé spaarprogramma',
                'points_per_euro' => 1,
                // punten → tegoed: redeem_points punten = redeem_value centen
                'redeem_points' => 100,
                'redeem_value' => 500,
                'min_redeem' => 100,
                'signup_bonus' => 50,
                // paid of fulfilled
                'award_on' => 'paid',
                'earn_text' => 'Verdien [points] punten met deze bestelling',
            ],
            'meta' => [
                'pixel_id' => null,
                'capi_token' => null,
                'test_event_code' => null,
                'domain_verification' => 'e7ycc9z4uuccw7llym1kpeepwi3eeg',
                'catalog_enabled' => true,
            ],
            'notifications' => [
                'from_name' => 'Orivé',
                'from_email' => 'info@orivenature.com',
                'bcc_orders' => null,
                'confirmation_intro' => 'Bedankt voor je bestelling! We gaan er direct mee aan de slag. Bestel je voor 22:00, dan is je pakket morgen al bij je in huis (NL/BE).',
                'shipped_intro' => 'Goed nieuws: je bestelling is onderweg! Met de link hieronder volg je je pakket.',
                'abandoned_enabled' => true,
                'abandoned_delay_hours' => 3,
                'abandoned_intro' => 'Je hebt nog iets lekkers in je winkelwagen laten staan. Rond je bestelling af wanneer het jou uitkomt.',
            ],
            'newsletter' => [
                'welcome_enabled' => true,
                // Kortingscode in de welkomstmail (beheer de code zelf bij Kortingen)
                'discount_code' => 'WELKOM10',
            ],
            'homepage' => [
                'sections' => self::homepageSections(),
            ],
        ];
    }

    public static function homepageSections(): array
    {
        return [
            ['type' => 'hero', 'data' => [
                'eyebrow' => '100% Biologisch Gecertificeerd',
                'heading_before' => 'van Aziatische bodem, met',
                'heading_highlight' => 'zorg',
                'heading_after' => 'gemaakt.',
                'text' => 'Biologische ingrediënten rechtstreeks van de bron in Azië. Zorgvuldig geselecteerd op kwaliteit en herkomst. Geen kunstmatige toevoegingen, geen onnodige tussenpersonen. Gewoon pure producten zoals de natuur ze bedoeld heeft.',
                'button_label' => 'bekijk onze essence',
                'button_link' => '#essence',
                'image_1' => 'home/home-1.png',
                'image_1_position' => 'left',
                'image_2' => 'home/home-1.png',
                'image_2_position' => 'right',
                'floaties' => ['products/matcha100.png', 'products/ube100g.png', 'products/Artboard1_b336aac3-de43-4b7c-a1f2-7c418edb8b35.png'],
                'flavours' => ['matcha-essence', 'ube-essence', 'dragon-fruit-essence'],
                'flavour_label' => 'kies je smaak',
                'badge_text' => '100% biologisch • geoogst in azië • gratis verzending •',
                'trust' => [
                    ['icon' => 'earth', 'text' => 'Geoogst in Azië'],
                    ['icon' => 'truck', 'text' => 'Gratis verzending (NL/BE)'],
                    ['icon' => 'clock', 'text' => 'Voor 22:00 besteld, morgen in huis'],
                ],
            ]],
            ['type' => 'ribbons', 'data' => [
                'top' => ['Geoogst in Azië', '100% Biologisch Gecertificeerd', 'Gratis verzending'],
                'bottom' => ['Matcha', 'Ube', 'Dragon Fruit', 'Tools'],
            ]],
            ['type' => 'essences', 'data' => [
                'heading' => 'Aziatische',
                'heading_italic' => 'Essence',
                'button_label' => 'alles bekijken',
                'button_link' => '/collections/aziatische-essence',
                'products' => ['matcha-essence', 'ube-essence', 'dragon-fruit-essence'],
                'assurance' => ['Gratis verzending in (NL/BE)', 'Voor 22:00 besteld, morgen in huis', '100% Biologisch Gecertificeerd'],
                'sticky_title' => 'Aziatische Essence',
            ]],
            ['type' => 'launch', 'data' => [
                'eyebrow' => 'nieuw',
                'heading_before' => 'Our new',
                'heading_italic' => 'Dragon Fruit',
                'heading_after' => 'powder is here',
                'text' => 'Ontdek 100% biologisch dragon fruit poeder met een levendige roze kleur en subtiel tropische smaak. Perfect voor lattes, smoothies, bowls en dagelijkse recepten.',
                'product' => 'dragon-fruit-essence',
                'button_label' => 'shop now',
                'video' => 'home/dragonfruit.mp4',
                'poster' => 'home/pic-3.png',
                'floaty' => 'products/Artboard1_b336aac3-de43-4b7c-a1f2-7c418edb8b35.png',
                'badge_text' => 'nieuw • new • nieuw • new • nieuw • new •',
            ]],
            ['type' => 'tiles', 'data' => [
                'tiles' => [
                    ['title' => 'Matcha', 'image' => 'home/004.jpg', 'link' => '/collections/matcha-essence', 'cta' => 'shop matcha'],
                    ['title' => 'Ube', 'image' => 'home/011.jpg', 'link' => '/collections/ube-essence', 'cta' => 'shop ube'],
                    ['title' => 'Dragon Fruit', 'image' => 'home/002.png', 'link' => '/collections/dragon-fruit', 'cta' => 'shop dragon fruit'],
                    ['title' => 'Tools', 'image' => 'home/box-003.png', 'link' => '/collections/tools', 'cta' => 'shop tools'],
                ],
            ]],
            ['type' => 'story', 'data' => [
                'eyebrow' => 'Ons verhaal',
                'text' => 'Wij oogsten biologische producten met aandacht voor herkomst, kwaliteit en puurheid. Eerlijk geteeld, verfijnd in smaak.',
                'button_label' => 'herkomst & missie',
                'button_link' => '/pages/over-ons',
            ]],
            ['type' => 'origin', 'data' => [
                'image' => 'home/farmer_2.jpg',
                'heading' => 'Van Aziatische Bodem,',
                'heading_italic' => 'Voor Jou',
                'text' => 'Met Orivé kies je voor biologisch geoogste essences uit Azië, samengesteld voor de momenten waarop jij even stilstaat en oprecht geniet.',
                'button_label' => 'bekijk onze essence',
                'button_link' => '#essence',
            ]],
            ['type' => 'lovers', 'data' => [
                'heading' => 'Onze Bewuste Liefhebbers',
                'button_label' => 'ontdek meer momenten',
                'button_link' => 'https://www.trustpilot.com/review/orivenature.com',
                'photos' => ['home/pic-2.jpg', 'home/pic-3.png', 'home/WhatsApp_Image_2026-09-08_at_21.44.20_1.jpg', 'home/pic-1.png', 'home/pic-4.jpg'],
            ]],
            ['type' => 'loyalty', 'data' => [
                'eyebrow' => 'Orivé spaarprogramma',
                'heading' => 'Spaar punten, krijg',
                'heading_italic' => 'shoptegoed',
                'text' => 'Bij elke bestelling spaar je automatisch punten. Wissel ze in voor shoptegoed op je volgende essence.',
                'steps' => [
                    ['icon' => 'user', 'title' => 'Maak een account', 'text' => 'Gratis, en je krijgt direct [bonus] welkomstpunten.'],
                    ['icon' => 'bag', 'title' => 'Bestel je favorieten', 'text' => 'Je verdient [points] punt per € 1 bij elke bestelling.'],
                    ['icon' => 'coin', 'title' => 'Wissel in voor tegoed', 'text' => '[rate], te gebruiken bij je volgende bestelling.'],
                ],
            ]],
            ['type' => 'faq', 'data' => [
                'heading' => 'Veelgestelde vragen',
                'help_title' => 'Hulp Nodig?',
                'help_text' => 'Heb je een vraag? Ons team is 24/7 bereikbaar voor al jouw vragen over bestellingen en ons assortiment.',
                'questions' => [
                    ['question' => 'Zijn jullie producten echt biologisch gecertificeerd?', 'answer' => '<p>Ja, alle Orivé essences zijn biologisch geoogst. Wij werken aan strikte biologische normen. Geen pesticiden, geen kunstmatige toevoegingen puur zoals de natuur het bedoeld heeft.</p>'],
                    ['question' => 'Waar komen jullie producten vandaan?', 'answer' => '<p>Onze essences worden zorgvuldig geoogst door onze boeren in Azië. Wij geloven in transparante herkomst en staan volledig achter elke stap van het oogstproces, van bodem tot jouw thuis.</p>'],
                    ['question' => 'Leveren jullie in heel Europa?', 'answer' => '<p>Ja, wij leveren door heel Europa. De verzendkosten worden berekend aan de checkout op basis van jouw locatie. Gratis verzending geldt voor bestellingen binnen Nederland en België.</p>'],
                    ['question' => 'Hoelang duurt de levering?', 'answer' => '<p><strong>Bestel je voor 22:00?</strong> Dan ontvang je jouw bestelling binnen Nederland en België de volgende dag al in huis. Voor de levertijden van overige Europese landen vind je actuele informatie aan de checkout. Zodra jouw pakket onderweg is ontvang je een bevestiging per e-mail met trackinginformatie.</p>'],
                    ['question' => 'Wat als ik niet tevreden ben met mijn bestelling?', 'answer' => '<p>Wij staan volledig achter de kwaliteit van onze producten. Ben je om welke reden dan ook niet tevreden? Neem contact met ons op via info@orivenature.com of ons <a href="/pages/contact">contactformulier</a> en wij lossen het altijd voor je op. Jouw tevredenheid is onze prioriteit.</p>'],
                    ['question' => 'Hoe gebruik ik jullie producten?', 'answer' => '<p>Bij elk product vind je een duidelijk gebruiksadvies. Heb je een specifieke vraag over een product? Wij helpen je graag verder via info@orivenature.com of ons <a href="/pages/contact">contactformulier</a>.</p>'],
                    ['question' => 'Zijn jullie producten geschikt voor beginners?', 'answer' => '<p>Of je nu voor het eerst kennismaakt met biologische Aziatische producten of al jaren fan bent onze producten zijn voor iedereen toegankelijk. Bij twijfel helpen wij je graag met persoonlijk advies.</p>'],
                    ['question' => 'Waarvoor kan ik jullie producten gebruiken?', 'answer' => '<p>Onze biologische essences zijn veelzijdig inzetbaar. Van een warme drank tot smoothies, desserts, ontbijt of bakproducten de mogelijkheden zijn eindeloos.</p>'],
                ],
            ]],
            ['type' => 'blog', 'data' => [
                'heading' => 'Blog',
                'heading_italic' => 'posts',
                'blog' => 'news',
                'count' => 4,
                'tag' => 'recept',
                'button_label' => 'View all',
            ]],
            ['type' => 'newsletter', 'data' => [
                'image' => 'home/pic-1.png',
                'sticker_big' => '10%',
                'sticker_small' => 'korting',
                'heading_italic' => 'Meld',
                'heading' => 'je aan voor onze nieuwsbrief',
                'text' => 'Meld je aan en ontvang 10% korting op je eerste bestelling, en word lid van onze community.',
            ]],
        ];
    }
}
