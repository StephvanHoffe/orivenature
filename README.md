# Orivé – speelse restyle

Een nieuwe, speelse homepage voor [orivenature.com](https://www.orivenature.com), gebouwd voor zo veel mogelijk conversie.
De inhoud (teksten, producten, prijzen, FAQ, blog, contactgegevens), de kleuren en de lettertypes zijn één-op-één overgenomen van de huidige site.

## Bekijken

Open `index.html` in een browser, of start een lokale server:

```bash
npx serve .
```

## Opbouw

| Bestand | Inhoud |
| --- | --- |
| `index.html` | Alle secties van de homepage |
| `assets/css/style.css` | Huisstijl (kleuren als CSS-variabelen), layout en alle animaties |
| `assets/js/main.js` | Winkelwagen, snel bekijken, inhoudskeuze, parallax en scroll-effecten |
| `assets/img/payment/` | Betaal-iconen uit de huidige footer |

Afbeeldingen en de video komen rechtstreeks van het Shopify-CDN van de winkel.

## Huisstijl (ongewijzigd)

- Olijfgroen `#52572E`, crème `#F3EFE3`, salie `#C9D6A9`, mauve `#CEB8CA`, antraciet `#2D2E2D`
- Koppen: **Libre Baskerville** · tekst: **Raleway**

## Wat er beweegt

- Zwevende productzakjes (mockups) in de hero die meebewegen met de muis, een morphende fotovorm en een draaiende badge
- Dragon Fruit-video in een telefoon-mockup, met een zwevend zakje en opstijgend "poeder"
- Productkaarten met 3D-tilt, zwevende zakjes en een wisselanimatie bij 50/100 gram
- Bij toevoegen vliegt het zakje naar de winkelwagen, met een confetti-burst
- Gekruiste USP-linten, een polaroid-carrousel, woorden die oplichten tijdens het scrollen, parallax
- Alle beweging staat uit bij `prefers-reduced-motion`

## Conversie-elementen

- **Direct kopen op de homepage**: inhoud kiezen, toevoegen en afrekenen zonder eerst naar een productpagina te hoeven
- **Winkelwagen-lade** met aantallen, subtotaal, aanvulling (Ritual box / andere smaak) en betaal-iconen
- **Afrekenen** gaat via een Shopify cart-permalink (`/cart/<variant-id>:<aantal>,…`) rechtstreeks naar de checkout van de winkel
- **Aftellen tot 22:00** (Nederlandse tijd) bij de belofte "Voor 22:00 besteld, morgen in huis"
- **"Bespaar"-label** bij 100 gram, berekend uit de bestaande prijzen (t.o.v. 2× 50 gram)
- Vertrouwen dicht bij de knoppen: gratis verzending, levertijd, biologisch, betaalmethoden
- **Snel bekijken** met fotogalerij en productbeschrijving
- Sticky "shop nu"-balk en WhatsApp-knop op mobiel, hulp-kaart met WhatsApp/e-mail bij de FAQ

## Onderhoud

Producten, prijzen en variant-ID's staan in `CATALOG` bovenin `assets/js/main.js`. Pas die aan als er in Shopify iets verandert,
en werk dan ook de startprijzen in de productkaarten in `index.html` bij.

De nieuwsbriefformulieren posten naar het klantformulier van Shopify (`/contact`, `form_type=customer`), en de land- en taalkeuze naar `/localization`.
