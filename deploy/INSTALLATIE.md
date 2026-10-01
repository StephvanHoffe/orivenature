# Orivé webshop installeren bij Vimexx (DirectAdmin)

Deze handleiding zet de nieuwe webshop op je Vimexx-hosting. Je hebt geen SSH of programmeerkennis nodig.

**Tip:** installeer de shop eerst op een testadres, bijvoorbeeld `nieuw.orivenature.com`. Je Shopify-winkel blijft dan gewoon draaien tot je tevreden bent. Overstappen doe je daarna in stap 10.

---

## 1. PHP-versie instellen

1. Log in op DirectAdmin.
2. Ga naar **Extra functies → Selecteer PHP-versie** (soms heet dit *PHP-versie beheren*).
3. Kies **PHP 8.3** (of hoger) voor het domein.
4. Zet deze extensies aan als je ze kunt kiezen: `intl`, `gd`, `pdo_mysql`, `mbstring`, `fileinfo`, `zip`. Bij Vimexx staan ze meestal al aan.

## 2. Domein of testdomein klaarzetten

- **Testen eerst (aanbevolen):** ga naar **Domeinbeheer → Domein toevoegen** en voeg `nieuw.orivenature.com` toe als *apart domein*, dus niet als subdomein. Dan krijgt het een eigen map `domains/nieuw.orivenature.com/public_html`. Maak bij je DNS een A-record voor `nieuw` naar het IP-adres van je Vimexx-hosting.
- **Direct live:** gebruik de map `domains/orivenature.com/`.

## 3. Database aanmaken

1. Ga naar **Accountbeheer → MySQL-beheer → Nieuwe database**.
2. Vul een naam in (bijv. `webshop`). DirectAdmin maakt er iets van als `gebruiker_webshop`.
3. Noteer de **databasenaam**, **gebruikersnaam** en het **wachtwoord**.

## 4. Bestanden uploaden

1. Ga naar **Systeeminfo & bestanden → Bestandsbeheer**.
2. Open de map van je domein, bijvoorbeeld `domains/nieuw.orivenature.com/`.
3. Staat er iets in `public_html` dat je wilt bewaren? Download dat dan eerst.
4. Upload `orive-webshop.zip` naar deze map en kies **Uitpakken** (Extract).
5. De map ziet er daarna zo uit:

```
domains/nieuw.orivenature.com/
├── orive/          ← de webshop zelf (niet openbaar)
└── public_html/    ← openbare bestanden (foto's, css, index.php)
```

Vraagt DirectAdmin of bestaande bestanden in `public_html` overschreven mogen worden? Kies dan **ja**.

## 5. Instellingen invullen (.env)

Open in Bestandsbeheer het bestand `orive/.env` en kies **Bewerken**. Vul in:

| Regel | Wat vul je in |
|---|---|
| `APP_URL` | Het adres van de shop, bijv. `https://nieuw.orivenature.com` |
| `DB_DATABASE` | Databasenaam uit stap 3 |
| `DB_USERNAME` | Database-gebruiker uit stap 3 |
| `DB_PASSWORD` | Database-wachtwoord uit stap 3 |
| `MAIL_PASSWORD` | Wachtwoord van de mailbox `info@orivenature.com` (DirectAdmin → E-mailbeheer) |

Laat de andere regels staan. Noteer de **INSTALL_TOKEN**; die heb je zo nodig.

## 6. SSL (https) aanzetten

Ga naar **Accountbeheer → SSL-certificaten**, kies **Let's Encrypt** en vink het domein aan. Zet daarna *Forceer SSL met https-redirect* aan.

## 7. Installatie starten

1. Ga in je browser naar `https://nieuw.orivenature.com/install`.
2. Kijk of alle punten een groen vinkje hebben. Een rood kruis bij *Databaseverbinding*? Controleer dan stap 5.
3. Vul de **installatiecode** (INSTALL_TOKEN), je naam, e-mailadres en een sterk wachtwoord in.
4. Laat **Producten, collecties, blog, pagina's en foto's overzetten** aangevinkt en klik op **Installeren**. Dit duurt 1 tot 3 minuten.
5. Maak daarna in `orive/.env` de regel `INSTALL_TOKEN=` leeg.

Inloggen op het beheer doe je voortaan via **/beheer**.

## 8. Cronjob instellen (herinneringsmails)

Ga naar **Geavanceerde functies → Cronjobs** en voeg een taak toe die **elke minuut** draait (`*` in alle velden):

```
/usr/local/bin/php /home/GEBRUIKERSNAAM/domains/nieuw.orivenature.com/orive/artisan schedule:run > /dev/null 2>&1
```

Vervang `GEBRUIKERSNAAM` door je DirectAdmin-gebruikersnaam en het domein door jouw domein. De cronjob stuurt onder andere de mails voor verlaten winkelwagens.

## 9. Laatste controle in /beheer

- **Instellingen → Betalingen:** plak je Mollie API-sleutel en klik op *Verbinding testen*. Zet *Testbetalingen toestaan* uit.
- **Instellingen → Verzending:** controleer de verzendkosten voor Europa (nu € 12,95, dit is een voorlopige waarde).
- **Instellingen → E-mails:** klik op *Testmail sturen*.
- **Marketing → Meta:** vul je pixel-ID en Conversions API-token in en klik op *Test-event sturen*.
- **Producten → Voorraad:** zet *Bijhouden* aan voor producten waarvan je de voorraad wilt bijhouden.
- Plaats een testbestelling, bijvoorbeeld met de Mollie test-sleutel.

## 10. Overstappen van Shopify

1. **Klanten en bestellingen:** exporteer in Shopify *Klanten* en *Bestellingen* als CSV en upload ze in **/beheer → Instellingen → Overzetten uit Shopify**.
2. **Domein omzetten:** pas bij je domeinbeheerder de DNS aan:
   - het A-record van `orivenature.com`, nu naar Shopify (`23.227.38.65`), gaat naar het IP-adres van je Vimexx-hosting;
   - het `www`-record, nu een CNAME naar `shops.myshopify.com`, gaat ook naar Vimexx.
   - Laat de MX-records (e-mail) ongewijzigd.
3. Installeer de shop op `domains/orivenature.com/` zoals hierboven, of verplaats de testinstallatie. Pas daarbij `APP_URL` in `.env` aan en leeg de cache via **/beheer → Instellingen → Onderhoud**.
4. Oude Shopify-links, zoals `/products/matcha-essence-50-gram`, worden automatisch doorgestuurd. Zie **Webshop → Doorverwijzingen**.
5. Zeg Shopify pas op als alles een paar dagen goed draait.

## Een nieuwe versie installeren

1. Maak in DirectAdmin een back-up van de database en van de map `orive`.
2. Upload de nieuwe zip en pak hem uit. Overschrijf alles **behalve** `orive/.env`, `public_html/uploads` en `public_html/media`.
3. Ga naar **/beheer → Instellingen → Onderhoud** en klik op **Database bijwerken** en daarna op **Cache legen**.

## Hulp bij problemen

- **Witte pagina of "Server Error":** zet in `.env` tijdelijk `APP_DEBUG=true`, bekijk de foutmelding en zet het daarna weer op `false`. Foutmeldingen staan ook in `orive/storage/logs/`.
- **Foto's laden niet:** controleer of de mappen `public_html/uploads` en `public_html/media` schrijfbaar zijn (rechten 755).
- **Mails komen niet aan:** controleer `MAIL_HOST`, `MAIL_USERNAME` en `MAIL_PASSWORD`. Stel bij je domein ook SPF en DKIM in (DirectAdmin → E-mailbeheer).
