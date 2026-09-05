# Paketradar — Package Compatibility Dashboard

Backend (+ eine schlanke Vue-SPA als Oberfläche) für ein Package Compatibility
Dashboard: erfasst Composer-Pakete nach Vendor, prüft sie automatisch gegen
konfigurierte Ziel-PHP-/Laravel-Versionen und stellt das Ergebnis über eine
öffentliche API sowie eine geschützte Admin-Oberfläche bereit.

npm-Unterstützung ist vorbereitet (Datenmodell, Ecosystem-Enum), aber noch
**nicht implementiert** — `packages:scan-vendor {vendor} npm` gibt aktuell nur
einen Hinweis aus.

## Features

- **Öffentliche API** (`/api/packages`, `/api/packages/{id}`, `/api/vendors`):
  filterbar nach Suche, Ecosystem, Vendor und Status, paginiert.
- **Erweiterte Felder für eingeloggte Nutzer** (Downloads, Favoriten,
  Abandoned-Flag, letzter Check) über dieselbe Resource-Klasse.
- **Dreistufige Kompatibilitätsprüfung** (`compatible` / `unclear` /
  `critical`) in `App\Services\CompatibilityChecker`, basierend auf
  Composer-Versions-Constraints (`composer/semver`) und Release-Alter.
- **Artisan Commands & Jobs** für das Scannen von Vendors, wöchentlich per
  Scheduler.
- **Admin-Bereich** (`/admin/vendors`, Blade + Standardformulare) zum
  Hinzufügen und erneuten Scannen von Vendors.
- **Vue-SPA** (`/`, `/packages`, `/login`) als Package Explorer mit Filtern,
  Paginierung und Detailansicht.

## Setup

```bash
cp .env.example .env
php artisan key:generate
composer install
php artisan install:api   # Sanctum-Migration/-Config (idempotent, meist schon vorhanden)
npm install
npm run build              # oder: npm run dev
```

Vor dem ersten `php artisan migrate --seed` müssen in `.env` gesetzt sein:

```
ADMIN_EMAIL=admin@example.test
ADMIN_PASSWORD=ein-sicheres-passwort
```

Es gibt **keinen Registrierungsflow** — der einzige Nutzer wird aus diesen
beiden Variablen per Seeder angelegt (`database/seeders/AdminUserSeeder.php`).
Ohne gesetzte Werte bricht der Seeder mit einer Fehlermeldung ab.

```bash
php artisan migrate --seed
```

Danach:

```bash
composer run dev   # Laravel-Server + Queue-Worker + Vite in einem Terminal
```

## Ziel-Versionen konfigurieren

Die geprüften PHP-/Laravel-Zielversionen liegen in `config/package_targets.php`
und werden über `TARGET_PHP_VERSION` / `TARGET_LARAVEL_VERSIONS` (kommagetrennt)
in `.env` gesteuert — keine eigene DB-Tabelle nötig.

## Vendors scannen

```bash
php artisan packages:scan-vendor laravel composer
php artisan packages:scan-all   # scannt alle bereits bekannten Vendors, läuft wöchentlich per Schedule
```

Alternativ über die Admin-Oberfläche unter `/admin/vendors` (Login erforderlich).

## Tests

```bash
php artisan test --compact
vendor/bin/pint --format agent
```

## Datenquelle

[Packagist](https://packagist.org) (`packages/list.json`,
`packages/{vendor}/{package}.json`) — ausschließlich lesende Zugriffe, keine
Schreibvorgänge auf fremde Repositories.
