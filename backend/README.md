# Dental Practi-Smart — Backend (Laravel API)

100% offline-capable REST API for a single dental clinic. All money is **whole IQD**
(no decimals anywhere). X-rays land on the local disk under `storage/app/public/xrays`.

## Install (one-time on the reception PC)

```bash
composer create-project laravel/laravel . "^11.0"      # use the existing app/, database/, routes/ files generated here
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

## Serve on the LAN

```bash
php artisan serve --host=0.0.0.0 --port=8000
# Tablets connect to http://192.168.1.50:8000
```

## Daily backup

```bash
php artisan clinic:backup                   # one-shot
# OR add cron to fire the scheduler:
# * * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

## API quick reference

| Method | Path                              | Purpose                            |
| ------ | --------------------------------- | ---------------------------------- |
| GET    | `/api/v1/queue`                   | Today's pending + active visits    |
| POST   | `/api/v1/visits`                  | Add a patient to the queue         |
| PATCH  | `/api/v1/visits/{id}/status`      | pending → active → completed       |
| POST   | `/api/v1/visits/{id}/xray`        | Multipart X-ray upload             |
| POST   | `/api/v1/visits/{id}/checkout`    | 3-method checkout engine           |
| GET    | `/api/v1/dashboard/metrics`       | Real-time financial dashboard      |
| GET    | `/api/v1/visits/archive`          | Completed visits, filterable       |
