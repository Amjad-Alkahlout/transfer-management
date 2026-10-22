# Money Transfer Management System

A Laravel application for managing remittance transfers between the UAE and Gaza branches. It includes a server-rendered Livewire interface, a versioned JSON API, transfer and payment workflows, capital accounts and ledger entries, manual exchange-rate management, and optional Telegram notifications.

## Features

### Transfers and payments

- Create transfers in either calculation mode: specify the receiver amount or specify the customer payment amount. Supported delivery methods are bank and wallet.
- Calculate the transfer amount, customer payable amount, and commission using the configured currency rates. Commission is entered in AED.
- Record an optional initial customer payment and subsequent partial or full payments. Payments update the transfer's paid, remaining, and payment-status values.
- Execute pending transfers with a required JPEG, PNG, or PDF proof upload (maximum 2 MiB). Execution records the completion time and user.
- Cancel pending transfers. Any collected payments are reversed from the corresponding UAE capital account. Administrators can force-cancel completed transfers after password confirmation and an audit note; the associated ledger entries are reversed.

Transfer statuses are `pending`, `completed`, and `cancelled`; payment statuses are `unpaid`, `partially_paid`, and `paid`. At execution, the payout amount and AED commission are converted to USD. Both are deducted from the Gaza USD capital account, and the commission is credited to the Gaza USD profit account.

### Capital accounts and ledger

- Maintain capital accounts by branch, currency, and account type (`capital` or `profit`). Profit accounts are restricted to Gaza. A branch/currency/type combination can have only one account.
- Record opening balances and subsequent account activity in the capital transaction ledger.
- Adjust account balances, withdraw profit, and transfer capital between accounts. Capital transfers support a preview, currency conversion, a transfer cost, and ledger records.
- A capital transfer debits its source amount from the source account and credits the converted amount to the destination account. Its transfer cost is converted to the destination currency and deducted from the corresponding Gaza profit account. The Gaza profit-to-UAE capital distribution path requires zero transfer cost.
- The current operating corridor is fixed in service logic: customer payments credit UAE capital accounts; transfer execution uses Gaza USD capital and profit accounts. Configure the required accounts before using these workflows. The database seeder does not create capital accounts.

### Currency and rates

The application supports USD, AED, and ILS. Exchange rates are stored as each currency's value relative to USD; conversion uses the ratio of the source and destination rates and rounds converted amounts to two decimal places. Rates are seeded with initial values and can be changed by an authorized user through `/exchange-rates` or `PUT /api/v1/exchange-rates/{currency}`.

Rate updates are manual. The repository does not implement synchronization with an external exchange-rate provider.

### Users, access, and localization

The application defines four roles: Administrator, Coordinator, Executor, and Transfer Executor. Laravel Gates enforce access in the web interface and API. Administrators pass the application's authorization gates; other roles have narrower access:

| Role | Main access |
| --- | --- |
| Administrator | User administration, capital ledger, all transfer and account workflows, and exchange-rate management |
| Coordinator | Create and edit eligible transfers, receive payments, cancel pending transfers, manage capital accounts and capital transfers, and view the dashboard |
| Executor | Execute transfers, manage exchange rates and capital accounts, view capital transfers and the dashboard |
| Transfer Executor | Execute transfers; does not have capital-account, capital-transfer, or dashboard access |

All roles can view the transfer list and transfer details when signed in. Transfer updates are limited by the workflow to pending, unpaid transfers.

The interface is localized in English (`en`) and Arabic (`ar`). The selected locale is stored in a browser cookie; the document uses left-to-right layout for English and right-to-left layout for Arabic. Users can update their timezone preference for displayed timestamps. The authenticated web interface also enforces one active session per user and device category (mobile/tablet or desktop).

### Telegram notifications

Users can link a Telegram chat through the application. Transfer-created, transfer-executed, and transfer-cancelled notifications are sent by queued listeners. Execution notifications include the uploaded proof. Configure `TELEGRAM_BOT_TOKEN` to enable communication with the bot. A queue worker must be running for queued notifications to be processed.

## Technology

- PHP `^8.5` and Laravel `^13.8` (`composer.json`)
- Livewire `^4.3` and Blade
- Tailwind CSS 4, Vite 8, Alpine.js, and npm
- MySQL 8.4 in the included Docker Compose stack; Laravel also has an SQLite connection for local/test use
- Laravel Sanctum for API bearer tokens
- Docker Compose, PHP-FPM, and Nginx configuration are included

Use `composer check-platform-reqs` to check the PHP extensions required by the installed Composer packages. The Docker image installs `pdo_mysql`, `mbstring`, `exif`, `pcntl`, `bcmath`, `gd`, and `zip`; a local installation also needs the PDO driver for its selected database.

## Local setup

You need PHP 8.5 or later compatible with `^8.5`, Composer 2, Node.js/npm, and a configured database. The checked-in `.env.example` contains MySQL connection placeholders and uses database-backed sessions, cache, and queues. Set `DB_CONNECTION=mysql` and provide working database settings for a local MySQL server, or configure SQLite explicitly as described below.

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Edit `.env` to match your local database and environment. For MySQL, set `DB_CONNECTION=mysql` and configure `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. Ensure the database exists, then run:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

For frontend hot reload, run `npm run dev` in a separate terminal instead of `npm run build`. The seeded data includes demo users and initial USD/AED/ILS exchange rates; it does not include capital accounts. Create the accounts required by the workflows in the application before recording customer payments or executing transfers. Treat seeded demo accounts as development-only and replace or remove them before deployment.

To use SQLite locally, set `DB_CONNECTION=sqlite` and `DB_DATABASE` to the path of an SQLite database file, create that file, and then run the migrations and seeders. The PHPUnit configuration uses in-memory SQLite and synchronous queues for tests.

## Running the application and queue

For the web interface:

```bash
php artisan serve
```

Run a worker in another terminal when processing Telegram notifications:

```bash
php artisan queue:work
```

The Composer `dev` script starts the Laravel server, a queue listener, Pail, and Vite together:

```bash
composer run dev
```

No scheduled application tasks are registered in `routes/console.php`.

## JSON API

The API is versioned under `/api/v1`. `POST /api/v1/auth/login` accepts `email`, `password`, and `device_name`, and returns a Sanctum bearer token. Send it as `Authorization: Bearer <token>` for protected endpoints. Inactive users cannot log in or use the API. `POST /api/v1/auth/logout` revokes the current token; `GET /api/v1/me` returns the authenticated user's profile. Login is throttled.

Representative request:

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"YOUR_EMAIL","password":"YOUR_PASSWORD","device_name":"my-client"}'
```

All other routes below require authentication and are subject to the same role gates as the application:

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/transfers` | List transfers |
| GET | `/api/v1/transfers/{transfer}` | Show a transfer |
| POST | `/api/v1/transfers/calculate` | Preview transfer calculation |
| POST | `/api/v1/transfers` | Create a transfer |
| PATCH | `/api/v1/transfers/{transfer}` | Update an eligible transfer |
| POST | `/api/v1/transfers/{transfer}/payments` | Receive a payment |
| POST | `/api/v1/transfers/{transfer}/execute` | Execute a transfer with proof |
| POST | `/api/v1/transfers/{transfer}/cancel` | Cancel a pending transfer |
| POST | `/api/v1/transfers/{transfer}/force-cancel` | Administrator cancellation of a completed transfer |
| GET | `/api/v1/dashboard/summary` | Dashboard summary |
| GET / PUT | `/api/v1/exchange-rates`, `/api/v1/exchange-rates/{currency}` | Read rates / update one rate |
| GET / POST | `/api/v1/capital-accounts` | List / create capital accounts |
| PATCH | `/api/v1/capital-accounts/{account}` | Update an account's editable details or active status |
| POST | `/api/v1/capital-accounts/{account}/adjustments` | Adjust an account balance |
| POST | `/api/v1/capital-accounts/{account}/profit-withdrawals` | Withdraw from a profit account |
| GET | `/api/v1/capital-transfers` | List capital transfers |
| POST | `/api/v1/capital-transfers/preview` | Preview a capital transfer |
| POST | `/api/v1/capital-transfers` | Create a capital transfer |
| GET | `/api/v1/capital-transactions` | View the capital transaction ledger |
| GET / POST | `/api/v1/users` | List / create users (administrator) |
| GET / PATCH / DELETE | `/api/v1/users/{user}` | Show / update / delete a user (administrator) |
| PUT | `/api/v1/users/{user}/password` | Change a user's password (administrator) |

The force-cancel request requires the administrator's password and audit notes. Proof uploads are multipart form data. Several financial mutation endpoints require an `Idempotency-Key`; consult [`docs/openapi.json`](docs/openapi.json) and the request/controller implementation for each operation's fields and response schema. Validation errors use HTTP 422; domain conflicts use HTTP 409. Missing exchange rates can make a conversion unavailable.

Inspect the live route table with:

```bash
php artisan route:list --path=api/v1
```

## Docker

`compose.yaml` defines the PHP application, queue worker, Nginx, and MySQL services. Build and start the configured stack with:

```bash
docker compose up -d --build
```

The included Nginx configuration is set for `transfer-hub.app` and redirects HTTP to HTTPS using certificates mounted from `/etc/letsencrypt`. Provision the expected certificates and hostname before using this configuration; it is not an out-of-the-box localhost HTTP setup. The entrypoint runs migrations and prepares storage on container start. Review `compose.yaml`, `Dockerfile`, and `docker-entrypoint.sh` before deploying or changing the deployment environment.

## Tests and checks

Run the PHPUnit suite or a focused group with Laravel's test runner:

```bash
php artisan test
php artisan test tests/Feature/Api/V1
php artisan test --filter=CapitalTransferModalTest
```

The repository currently includes example tests, API feature tests, an OpenAPI contract test, and a capital transfer modal regression test. In the current Windows Codex sandbox, PHPUnit has been observed to exit before running tests because PHP's `is_readable()` returns `false` for `vendor/autoload.php` even though the file exists and PHP can open and require it. PHPUnit checks `is_readable()` for its bootstrap. This is an environment-specific PHP file-access discrepancy; verify `is_readable('vendor/autoload.php')` with the PHP executable used to run tests if that error occurs.

Build frontend assets with:

```bash
npm run build
```

The repository does not configure an application scheduler. Background queued notifications require a queue worker as described above.
