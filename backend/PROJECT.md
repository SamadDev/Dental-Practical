# Dental Practi-Smart — Backend

A 100% offline-capable Laravel 11 REST API for a single dental clinic. Runs on the
reception PC, serves tablets over the LAN, and survives without internet. All money
is whole **IQD** (no decimals, no floats — `bigInteger` everywhere). X-rays land on
the local disk under `storage/app/public/xrays`.

## Stack

| Layer    | Choice                                                          |
| -------- | --------------------------------------------------------------- |
| Runtime  | PHP **8.2+**                                                    |
| Framework| Laravel **11.31**                                               |
| DB       | MySQL (driven by `mysqldump` in the backup command)             |
| Storage  | Local public disk for X-ray images                              |
| Auth     | None — trusted LAN deployment                                   |
| Frontend | Separate (tablets) — this repo is API-only under `/api/v1/*`    |

## Domain model

```
Patient ──┬──< Visit >──── (xray file on disk)
          │       │
          │       └─── belongsTo ─── AqsatContract
          └──< AqsatContract
                  └──< Visit (payments draw down remaining_balance)

Expense  (standalone — clinic operating costs)
```

### Tables

- **patients** — `name`, `phone`, `age`, `is_smoker`, `medical_notes`
- **visits** — one row per appointment; tracks queue state and money for that visit
  - `queue_status`: `pending | active | completed`
  - `visit_type`: `walk_in | phone | whatsapp`
  - money columns (whole IQD): `total_cost`, `amount_paid`, `short_term_debt`
  - `xray_path` — relative path on the public disk
- **aqsat_contracts** — installment plans for expensive treatments
  - `total_amount`, `remaining_balance` (whole IQD), `status`: `active | completed | cancelled`
- **expenses** — operating costs (`amount` whole IQD, `description`)

Every money column is `unsignedBigInteger` and cast to `integer` on the model, so
arithmetic never drifts into floats — IQD has no fractional unit and the JSON
output is guaranteed to be a whole number.

## Core workflows

### 1. Daily queue

`GET /api/v1/queue` returns today's `pending` + `active` visits, with `active`
pinned to the top (`FIELD(queue_status, 'active', 'pending')`). Completed visits
fall off the queue automatically (they only show in the archive).

State transitions are single-click via
`PATCH /api/v1/visits/{id}/status` with one of `pending | active | completed`.

### 2. The 3-Methodology Checkout Engine

`POST /api/v1/visits/{id}/checkout` is the source of truth — the frontend's math
is **re-verified server-side** inside a DB transaction.

| Method        | Effect                                                                     |
| ------------- | -------------------------------------------------------------------------- |
| `full_cash`   | Forces `amount_paid = total_cost`; `short_term_debt = 0`                   |
| `short_debt`  | `short_term_debt = total_cost - amount_paid` (rejects overpay)             |
| `aqsat`       | Locks the contract row, decrements `remaining_balance`, marks `completed` when it hits 0; no short-term debt is created |

After checkout the visit is set to `queue_status = completed` and leaves the queue.

### 3. X-ray upload

`POST /api/v1/visits/{id}/xray` accepts multipart `xray` (jpg/jpeg/png/webp, max
20 MB). Old image is deleted before the new one is stored on the `public` disk
under `xrays/`.

### 4. Treatment Archive

`GET /api/v1/visits/archive` — completed visits, filterable by `from`, `to`,
`smokers_only`, `with_debt`; paginated (`per_page`, default 25).

### 5. Financial dashboard

`GET /api/v1/dashboard/metrics?from=&to=` returns:

- `total_cash_collected` — Σ `visits.amount_paid` in range
- `active_customer_debt` — Σ `visits.short_term_debt` in range
- `upcoming_aqsat_revenue` — Σ `aqsat_contracts.remaining_balance` where `status='active'` (not date-filtered — forward-looking pipeline)
- `total_expenses` — Σ `expenses.amount` in range
- `true_net_profit` — `total_cash_collected − total_expenses`
- `currency: "IQD"`

Every value is cast to `int` before serialisation.

## API surface

All routes are under `/api/v1`. No auth middleware.

| Method | Path                                | Purpose                                |
| ------ | ----------------------------------- | -------------------------------------- |
| GET    | `/patients`                         | List + search (`search`, `smokers_only`, `has_debt`, `per_page`) |
| POST   | `/patients`                         | Create patient                         |
| GET    | `/patients/{id}`                    | Patient + visits + contracts + outstanding debt |
| PATCH  | `/patients/{id}`                    | Update                                 |
| DELETE | `/patients/{id}`                    | Delete (cascades to visits + contracts)|
| GET    | `/queue`                            | Today's pending + active visits        |
| POST   | `/visits`                           | Add visit (auto `pending`)             |
| PATCH  | `/visits/{id}`                      | Edit `treatment_notes`, `total_cost`   |
| PATCH  | `/visits/{id}/status`               | Queue state transition                 |
| POST   | `/visits/{id}/xray`                 | Multipart X-ray upload                 |
| POST   | `/visits/{id}/checkout`             | 3-method checkout engine               |
| GET    | `/visits/archive`                   | Completed visits + filters             |
| GET    | `/aqsat-contracts`                  | List, filter by `patient_id`, `status` |
| POST   | `/aqsat-contracts`                  | Create (sets `remaining_balance = total_amount`) |
| GET    | `/aqsat-contracts/{id}`             | With patient + visits                  |
| PATCH  | `/aqsat-contracts/{id}`             | Update `treatment_name`, `status`      |
| GET    | `/expenses`                         | List, filter by `from`/`to`            |
| POST   | `/expenses`                         | Create                                 |
| DELETE | `/expenses/{id}`                    | Delete                                 |
| GET    | `/dashboard/metrics`                | Real-time financial dashboard          |

## Backups

`php artisan clinic:backup` writes:

- `db_<YYYY-MM-DD_HHMMSS>.sql` — `mysqldump` of the configured MySQL DB
- `xrays_<YYYY-MM-DD_HHMMSS>.zip` — every file under `storage/app/public/xrays`

Destination is `--path=…` → `BACKUP_PATH` env → `storage/app/backups`
(point `BACKUP_PATH` at a mounted USB drive for off-PC retention).

Scheduled in `app/Console/Kernel.php` at **23:00 daily**; failures are logged.
For the schedule to fire, the system cron must call
`php artisan schedule:run` every minute:

```cron
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Project layout

```
app/
  Console/
    Commands/BackupLocal.php       # clinic:backup
    Kernel.php                     # daily 23:00 schedule
  Http/Controllers/Api/
    PatientController.php
    VisitController.php            # queue, store, status, xray, checkout, archive
    AqsatContractController.php
    ExpenseController.php
    DashboardController.php
  Models/
    Patient.php
    Visit.php
    AqsatContract.php
    Expense.php
database/migrations/
  2025_01_01_000001_create_patients_table.php
  2025_01_01_000002_create_aqsat_contracts_table.php
  2025_01_01_000003_create_visits_table.php
  2025_01_01_000004_create_expenses_table.php
routes/
  api.php                          # all /api/v1 routes
  web.php
```

## Conventions

- **Money is integer IQD, always.** `unsignedBigInteger` in the schema, `'integer'`
  cast on the model. Never introduce decimals or floats.
- **Server is the source of truth for checkout math.** Frontend can compute hints,
  but the API recomputes inside a DB transaction and rejects bad inputs (422).
- **Aqsat lock.** The installment contract row is `lockForUpdate()` during checkout
  to prevent concurrent overdraw on the LAN.
- **Daily queue boundary.** `whereDate('created_at', today())` — the queue is a
  today-only view; yesterday's pending visits do not roll over.
- **No auth.** This API assumes a trusted LAN and ships without authentication
  middleware. Do not expose to the public internet.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link            # required for X-ray URLs
php artisan serve --host=0.0.0.0 --port=8000
```

Tablets connect to `http://<reception-pc-ip>:8000`.
