# 🦷 Dental Practi-Smart  (IQD · RTL edition)

A 100 % offline, LAN-only clinic management SPA — **Laravel API + Vue 3** — built
for a single dental clinic running off the reception PC. Tablets and laptops
join over the clinic Wi-Fi and hit `http://192.168.1.50:8000/api/v1`.

## Project layout

```
supliment/
├── backend/      Laravel 11 API · MySQL · whole-IQD bigInteger money
│   ├── app/Http/Controllers/Api/   PatientController, VisitController, …
│   ├── app/Models/                 Patient, Visit, AqsatContract, Expense
│   ├── app/Console/Commands/       BackupLocal (daily .sql + xrays.zip)
│   ├── database/migrations/        4 strict-typed migrations
│   └── routes/api.php              /api/v1/*
└── frontend/     Vue 3 SPA · Vite · Tailwind · vue-i18n · Pinia
    ├── src/App.vue                 :dir="lang.dir"  (LTR ⇄ RTL)
    ├── src/components/             SmokerBadge, StatusBadge, IqdInput,
    │                               CheckoutDialog (3-method engine), …
    ├── src/views/                  QueueView, PatientView, ArchiveView,
    │                               DashboardView, ExpensesView
    ├── src/locales/                en.json + ku.json (Kurdish Sorani)
    └── src/assets/fonts/           Bundled Vazirmatn + Inter woff2
```

## What you get

| Feature                                          | Where                                |
| ------------------------------------------------ | ------------------------------------ |
| Whole-IQD math (no floats anywhere)              | bigInteger columns + integer casts   |
| Unified daily queue, single-click state machine  | `QueueView.vue` + `PATCH /visits/:id/status` |
| Smoker tag alert (🚬) on queue + records         | `SmokerBadge.vue`                    |
| Native-camera X-ray upload to local disk         | `<input capture="environment">` → `storage/app/public/xrays` |
| 3-method checkout (Cash · Short Debt · Aqsat)    | `CheckoutDialog.vue` + `VisitController::checkout` |
| Dynamic category-free expense quick form         | `ExpensesView.vue`                   |
| Real-time KPIs (cash, debt, aqsat, net profit)   | `DashboardController::metrics`       |
| Smart-filtered treatment archive                 | `ArchiveView.vue` + `/visits/archive`|
| Print-to-PDF audit (`@media print`)              | `assets/main.css`                    |
| Daily local SQL + X-ray backup to USB            | `php artisan clinic:backup`          |
| Full LTR / RTL with Kurdish Sorani               | `:dir`, `ps-/pe-/ms-/me-` utilities  |
| Offline-bundled Vazirmatn + Inter fonts          | `src/assets/fonts/`                  |

## Run

### Backend (one time on the reception PC)
```bash
cd backend
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate && php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

### Frontend
```bash
cd frontend
npm install
cp .env.example .env          # set VITE_API_BASE to the reception PC's LAN IP
npm run build                  # produces dist/ — serve statically, or
npm run dev                    # for hot-reload during development
```

### Daily backup cron
```cron
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Currency contract

**Every money field is a strict whole integer in IQD.** No decimals, ever.

- DB: `unsignedBigInteger` columns
- PHP: Eloquent `$casts => 'integer'`
- Server: `'integer|min:0'` validation rules on every checkout request
- Client: `IqdInput.vue` strips non-digits on every keystroke; `formatIQD()`
  truncates with `Math.trunc` before display
- Checkout engine: re-derives `short_term_debt = total_cost - amount_paid`
  on the server inside a DB transaction — the client number is never trusted

## Direction contract

The root `<div :dir="lang.dir">` flips between `ltr` and `rtl`. The rule:
**never** use `pl-*`, `pr-*`, `ml-*`, `mr-*`, `left-*`, `right-*`. Always
use logical equivalents — `ps-*`, `pe-*`, `ms-*`, `me-*`, `start-*`, `end-*`
— so the UI mirrors perfectly when the user taps the language toggle.
