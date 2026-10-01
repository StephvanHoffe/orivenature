# Orivé webshop

Eigen webshop voor [orivenature.com](https://www.orivenature.com), ter vervanging van Shopify. Het ontwerp is speels en geanimeerd. Kleuren, lettertypen en teksten zijn gelijk aan de huidige winkel. Het beheer zit in **/beheer**.

Gebouwd met Laravel 13, Filament 5 (beheer) en Mollie (betalingen). Draait op gewone gedeelde hosting met PHP 8.3 en MySQL, zoals Vimexx met DirectAdmin. Je hebt geen Node of SSH nodig.

## Wat zit erin

**Winkel**
- Startpagina met bewegende onderdelen. Elke sectie is aan te passen via *Webshop → Startpagina*.
- Collecties met sortering, productpagina's met varianten (50 g / 100 g), snel bekijken, zoeken, blog en pagina's.
- Winkelwagen als zijpaneel met aanbevelingen, gratis-verzending-hints en spaarpunten.
- Afrekenen zonder afleiding:
  - kortingscodes, cadeaubonnen, tegoed en verzendzones;
  - account aanmaken tijdens het afrekenen;
  - betalen via Mollie (iDEAL | Wero, Klarna, PayPal, creditcard, Apple Pay…).
- Klantaccount met bestellingen, adressen en het spaarprogramma.
- Zelfde adressen als Shopify (`/products/…`, `/collections/…`, `/pages/…`, `/blogs/news/…`). Oude links worden doorverwezen.
- SEO: meta-tags, gestructureerde data, `sitemap.xml` en `robots.txt`.
- Foto's worden automatisch verkleind naar WebP.

**Beheer (/beheer)**
- Overzicht: omzet, bestellingen, bezoekers, conversie, een verkooptrechter en de best verkochte producten.
- Bestellingen:
  - verzenden met track & trace en terugbetalen via Mollie (deels kan ook);
  - annuleren, factuur en pakbon als PDF, notities, export naar CSV;
  - verlaten winkelwagens met automatische herinneringsmail.
- Producten, collecties (volgorde slepen), voorraad en cadeaubonnen.
- Klanten:
  - bestelgeschiedenis en adressen;
  - punten en tegoed aanpassen;
  - import en export, nieuwsbrief-abonnees en berichten van het contactformulier.
- Marketing:
  - kortingen: percentage, vast bedrag of gratis verzending, ook automatisch en met deelbare link `/discount/CODE`;
  - het eigen spaarprogramma;
  - Meta: pixel, Conversions API en productcatalogus.
- Webshop: pagina's, blog, navigatie, doorverwijzingen, weergave-instellingen en SEO.
- Instellingen:
  - winkelgegevens, betalingen (Mollie), verzending, afrekenen en e-mails;
  - gebruikers met rollen;
  - overzetten uit Shopify en onderhoud (database bijwerken zonder SSH).

**Spaarprogramma**
- Klanten sparen punten per bestede euro, standaard 1 punt per € 1. Ze krijgen welkomstpunten bij een account.
- Ze wisselen punten in voor shoptegoed in *Mijn account*. Standaard is 100 punten € 5 waard.
- Het tegoed wordt automatisch gebruikt bij het afrekenen.
- Bij annuleren of volledig terugbetalen worden punten teruggedraaid.
- Gasten sparen ook. Hun punten staan klaar zodra ze een account activeren.

## Installeren

Zie **[deploy/INSTALLATIE.md](deploy/INSTALLATIE.md)** voor de stappen bij Vimexx/DirectAdmin.

Het uploadpakket maak je met:

```bash
deploy/build.sh              # build/orive-webshop.zip
deploy/build.sh --met-fotos  # inclusief de foto's uit public/uploads
```

## Lokaal ontwikkelen

```bash
composer install
cp .env.example .env && php artisan key:generate   # vul DB_* in
php artisan migrate
php artisan orive:import-shopify                   # producten, blog, pagina's en foto's uit orivenature.com
php artisan orive:eigenaar jij@example.com         # beheer-account (wachtwoord wordt getoond)
php artisan serve
```

Zonder Mollie-sleutel kun je in de testomgeving afrekenen met een gesimuleerde betaling.

Andere commando's:

| Commando | Wat het doet |
|---|---|
| `php artisan orive:import-shopify --customers=klanten.csv --orders=bestellingen.csv --award-points` | Klanten en bestellingen uit Shopify-CSV's importeren |
| `php artisan orive:verlaten-winkelwagens` | Herinneringen sturen; draait elk kwartier via `schedule:run` |
| `php artisan test` | Tests uitvoeren; gebruikt de MySQL-database `orive_test` |

## Opbouw

| Map | Inhoud |
|---|---|
| `app/Services` | Winkelwagen, prijsberekening, bestellingen, spaarprogramma, betalingen (Mollie), Meta, statistieken, import |
| `app/Http/Controllers/Shop` | Winkel, afrekenen, account |
| `app/Filament` | Beheeromgeving: resources, instellingenpagina's, widgets |
| `resources/views/shop` | Winkel-templates (Blade) |
| `public/storefront` | CSS, JavaScript, lettertypen en iconen van de winkel (geen build-stap nodig) |
| `app/Support/SettingDefaults.php` | Standaardwaarden voor alle instellingen |
