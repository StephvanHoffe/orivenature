#!/usr/bin/env bash
# Maakt een zip-bestand om de webshop op gedeelde hosting (Vimexx / DirectAdmin) te zetten.
#
#   deploy/build.sh               -> build/orive-webshop.zip (foto's worden bij de installatie opgehaald)
#   deploy/build.sh --met-fotos   -> neemt ook public/uploads mee (groter bestand)
#   deploy/build.sh --delen       -> maakt daarnaast delen van max. ±25 MB (orive-webshop-deel-1.zip, -2, …)
#                                    die je allemaal in dezelfde map uitpakt
#
# Het pakket bevat twee mappen:
#   orive/        de applicatie (komt NAAST public_html te staan)
#   public_html/  de openbare bestanden (vervangt de inhoud van public_html)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/build"
PKG="$OUT/pakket"
WITH_PHOTOS=0
SPLIT=0
for arg in "$@"; do
  [[ "$arg" == "--met-fotos" ]] && WITH_PHOTOS=1
  [[ "$arg" == "--delen" ]] && SPLIT=1
done

rm -rf "$PKG" "$OUT"/orive-webshop*.zip
mkdir -p "$PKG/orive" "$PKG/public_html"

echo "→ Applicatie kopiëren"
rsync -a "$ROOT/" "$PKG/orive/" \
  --exclude .git --exclude .github --exclude node_modules --exclude vendor --exclude build --exclude tests \
  --exclude public --exclude .env --exclude .phpunit.result.cache --exclude phpunit.xml \
  --exclude 'storage/logs/*.log' --exclude 'storage/framework/cache/data/*' --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*.php' --exclude 'storage/app/private/*' --exclude 'storage/app/livewire-tmp/*' \
  --exclude 'bootstrap/cache/*.php'

echo "→ Composer (zonder ontwikkelpakketten)"
(cd "$PKG/orive" && composer install --no-dev --optimize-autoloader --no-interaction --quiet --no-scripts \
  && php artisan package:discover --ansi > /dev/null)
# Git-mappen en documentatie van pakketten zijn op de server niet nodig
find "$PKG/orive/vendor" -type d \( -name .git -o -name .github \) -prune -exec rm -rf {} +
find "$PKG/orive/vendor" -mindepth 3 -maxdepth 3 -type d \( -name tests -o -name test -o -name docs -o -name doc \) -prune -exec rm -rf {} +

echo "→ Openbare bestanden"
rsync -a "$ROOT/public/" "$PKG/public_html/" --exclude 'media/*' --exclude 'uploads/*' --exclude hot
[[ $WITH_PHOTOS == 1 ]] && rsync -a "$ROOT/public/uploads/" "$PKG/public_html/uploads/" --exclude .gitignore
mkdir -p "$PKG/public_html/uploads" "$PKG/public_html/media"
# Laravel zoekt de app één map hoger in "orive"
sed -i "s#__DIR__.'/../#__DIR__.'/../orive/#g" "$PKG/public_html/index.php"

echo "→ .env met nieuwe sleutels"
KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
TOKEN="$(php -r 'echo bin2hex(random_bytes(12));')"
cat > "$PKG/orive/.env" <<ENV
APP_NAME="Orivé"
APP_ENV=production
APP_KEY=$KEY
APP_DEBUG=false
# Vul hier het adres van de winkel in (met https://)
APP_URL=https://www.orivenature.com
APP_TIMEZONE=Europe/Amsterdam
APP_LOCALE=nl
APP_FALLBACK_LOCALE=nl

# Eenmalige installatiecode voor /install. Maak leeg na de installatie.
INSTALL_TOKEN=$TOKEN

LOG_CHANNEL=daily
LOG_LEVEL=warning

# Database (aanmaken in DirectAdmin > MySQL-beheer)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=240
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=public

# E-mail (een mailbox uit DirectAdmin > E-mailbeheer, bijv. info@orivenature.com)
MAIL_MAILER=smtp
MAIL_HOST=mail.orivenature.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=info@orivenature.com
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="info@orivenature.com"
MAIL_FROM_NAME="Orivé"

# Mollie kun je ook later instellen in /beheer > Instellingen > Betalingen
MOLLIE_KEY=
ENV

cp "$ROOT/deploy/INSTALLATIE.md" "$PKG/INSTALLATIE.md"

echo "→ Zip maken"
(cd "$PKG" && zip -qr "$OUT/orive-webshop.zip" orive public_html INSTALLATIE.md)
du -h "$OUT/orive-webshop.zip"

if [[ $SPLIT == 1 ]]; then
  echo "→ Delen maken"
  PART=1
  ZIP="$OUT/orive-webshop-deel-$PART.zip"
  (cd "$PKG" && zip -qr "$ZIP" INSTALLATIE.md public_html orive -x 'orive/vendor/*')
  for dir in "$PKG"/orive/vendor/*; do
    if [[ $(stat -c %s "$ZIP") -gt 17000000 ]]; then
      PART=$((PART + 1))
      ZIP="$OUT/orive-webshop-deel-$PART.zip"
    fi
    (cd "$PKG" && zip -qr "$ZIP" "orive/vendor/$(basename "$dir")")
  done
  du -h "$OUT"/orive-webshop-deel-*.zip
fi
echo "Installatiecode (INSTALL_TOKEN): $TOKEN"
