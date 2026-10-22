# 💸 Money Transfer Management System

> **Educational & Portfolio Project**  
> Developed by **Amjad Alkahloot** (Computer Science Engineering Student, Andhra University).  
> Repository: [Amjad-Alkahlout/transfer-management](https://github.com/Amjad-Alkahlout/transfer-management)

A full-stack financial remittance and cash-flow management system built with **Laravel 12**, **Livewire**, **Tailwind CSS**, and **MySQL**. The application models cross-border money transfer operations between two operating branches: **UAE** (collection / sender side) and **Gaza** (payout / receiver side), featuring automated multi-currency calculations, double-entry capital account ledgers, receipt proof uploads, single-device session enforcement, and event-driven Telegram notifications.

---

## 📋 Table of Contents

- [Overview & Target Users](#overview--target-users)
- [Architecture & Tech Stack](#architecture--tech-stack)
- [Implemented Transfer Workflow](#implemented-transfer-workflow)
- [Currency Calculations & Pricing Engine](#currency-calculations--pricing-engine)
- [Financial Management & Capital Accounting](#financial-management--capital-accounting)
- [User Roles & Permissions (RBAC)](#user-roles--permissions-rbac)
- [Telegram Bot Integration](#telegram-bot-integration)
- [Security & Session Management](#security--session-management)
- [Versioned JSON API](#versioned-json-api)
- [Prerequisites & Local Installation](#prerequisites--local-installation)
- [Docker Environment Setup](#docker-environment-setup)
- [Environment Configuration](#environment-configuration)
- [Running Queues, Seeders & Tests](#running-queues-seeders--tests)
- [Deployment Verification](#deployment-verification)
- [Current Limitations & Planned Features](#current-limitations--planned-features)
- [Author & License](#author--license)

---

## Overview & Target Users

The system is designed to streamline informal and cross-border money transfer office workflows, where remittances are collected from senders in one jurisdiction and disbursed in cash or bank transfer to receivers in another.

### Target Personas

- **Remittance Coordinators**: Create transfers, calculate client payable amounts, receive full or partial customer payments in the UAE branch, and issue cancellations when needed.
- **Payout Executors**: Review pending disbursements, disburse funds to recipients in Gaza (via bank deposit or mobile wallet), and upload signed transfer receipts/proofs.
- **Transfer Executors**: Restricted payout operators tasked exclusively with executing disbursements and uploading payment receipts.
- **Financial Administrators**: Manage branch capital accounts, monitor exchange rates relative to USD, perform manual balance adjustments, withdraw or distribute profits, audit the full capital ledger, manage user accounts, and execute administrative force-cancellations.

---

## Architecture & Tech Stack

The application employs a layered Laravel architecture using Livewire (Volt-style single-file components) for dynamic, reactive UI interactions without dedicated SPA frontend frameworks.

```
                      +-----------------------------+
                      |         Nginx (Web)         |
                      |   (HTTP/80 -> HTTPS/443)    |
                      +--------------+--------------+
                                     |
                                     v
                      +-----------------------------+
                      |     PHP-FPM Application     |
                      |  (Laravel 12 / Livewire 4)  |
                      +-------+-------------+-------+
                              |             |
           +------------------+             +------------------+
           v                                                   v
+---------------------+                             +---------------------+
|    MySQL 8.4 DB     |                             | Laravel Queue Worker|
|  (Transactions,     |                             | (Database driver:   |
|   Transfers, Users) |                             |  Telegram alerts)   |
+---------------------+                             +---------------------+
```

### Technology Stack

| Layer | Technologies |
| :--- | :--- |
| **Backend Framework** | Laravel 12.x (`laravel/framework ^13.8` skeleton), PHP 8.5+ |
| **Reactive UI** | Livewire 4 (`livewire/livewire ^4.3`), Alpine.js (`alpinejs ^3.15`) |
| **Styling & Assets** | Tailwind CSS 4 (`tailwindcss ^4.0`, `@tailwindcss/vite`), Vite 8, Blade Heroicons |
| **Database** | MySQL 8.4 (using `pdo_mysql`, database sessions, and database queue jobs) |
| **Device Detection** | Jenssegers Agent (`jenssegers/agent ^2.6`) for mobile/desktop session enforcement |
| **Notifications** | Telegram Bot API via Laravel Queues and Webhooks |
| **Localization** | Bilingual Arabic (`ar`, RTL with IBM Plex Sans Arabic) and English (`en`) with cookie persistence |
| **Containerization** | Docker Engine & Docker Compose (multi-stage Node & PHP-FPM builds, Nginx 1.29 Alpine, MySQL 8.4) |

---

## Implemented Transfer Workflow

The system enforces a strict operational lifecycle across four transfer states:

```
                  [ Transfer Created ]
                  (Status: PENDING)
                  (Payment: UNPAID)
                          │
          ┌───────────────┴───────────────┐
          ▼                               ▼
 [ Customer Payment ]            [ Payout Execution ]
 (Full or Partial)               (Proof Upload Required)
 (Payment: PAID / PARTIALLY)     (Status: COMPLETED)
          │                               │
          │                               ▼
          │                      [ Admin Force-Cancel ]
          │                      (Password Confirmed;
          │                       Full Capital Reversal)
          ▼                               │
 [ Coordinator Cancel ]                   │
 (Pending Transfers Only)                 │
          │                               │
          └───────────────┬───────────────┘
                          ▼
                 [ Transfer Cancelled ]
                 (Status: CANCELLED)
```

### 1. Transfer Creation
- Performed by **Coordinators** or **Administrators**.
- Generates an auto-formatted unique reference number (`TRF-XXXXXX`).
- Captures recipient details:
  - **Receiver Name**
  - **Delivery Method**: `bank` (requires bank account number) or `wallet` (requires wallet phone number).
  - **Notes & Instructions**.
- Requires calculation mode, requested currency, customer payable currency, and commission in AED.
- Allows recording an optional **initial customer payment** immediately upon creation.
- Dispatches a `TransferCreated` event.

### 2. Payment Collection
- Remittance sender payments are collected in the **UAE** branch.
- Supports installment payments:
  - `unpaid`: Zero payment collected.
  - `partially_paid`: $0 < \text{Paid Amount} < \text{Customer Payable Amount}$.
  - `paid`: $\text{Remaining Amount} \le 0$.
- Each received payment credits the corresponding UAE Capital Account in that currency, logging a `customer_transfer` transaction.

### 3. Payout Execution
- Executed by **Executors**, **Transfer Executors**, or **Administrators**.
- Requires the transfer status to be `PENDING`.
- **Proof of Execution**: Mandatory file upload (JPEG, PNG, or PDF, up to 2MB) stored on the `public` storage disk (`transfer_proofs/`).
- **Capital Deductions**:
  - Deducts the net transfer amount (converted to USD) from the **Gaza Capital Account** (`customer_transfer`, direction: `OUT`).
  - Deducts the commission (converted to USD) from the **Gaza Capital Account** (`transfer_expense`, direction: `OUT`).
  - Credits the commission (in USD) to the **Gaza Profit Account** (`customer_transfer`, direction: `IN`).
  - Records a profit entry in `ProfitAccountTransaction`.
- Updates transfer status to `COMPLETED` with timestamp and executor user ID.
- Dispatches a `TransferExecuted` event.

### 4. Cancellation & Administrative Rollbacks
- **Standard Cancellation** (`cancel-transfer` Gate: Coordinator / Admin):
  - Allowed only for transfers with status `PENDING`.
  - Automatically reverses any collected customer payments by debiting the UAE Capital account.
  - Deletes recorded payment receipts and sets status to `CANCELLED`.
  - Dispatches a `TransferCancelled` event.
- **Admin Force-Cancellation** (`force-cancel-completed-transfer` Gate: Admin only):
  - Permitted on transfers already marked `COMPLETED`.
  - Requires the Administrator to confirm with their account password.
  - Reverses all associated capital transactions (both Gaza capital/expense deductions and Gaza profit credits) using `post_completion_cancellation` transaction types.
  - Deletes profit transactions and payment records, then transitions status to `CANCELLED`.

---

## Currency Calculations & Pricing Engine

The system supports three currencies: **USD**, **AED**, and **ILS** (Israeli New Shekel). All conversion rates are anchored against **USD** (`rate_to_usd`).

$$\text{Exchange Rate}(A \to B) = \frac{\text{Rate to USD}(A)}{\text{Rate to USD}(B)}$$

$$\text{Converted Amount} = \text{Round}\left(\text{Amount} \times \text{Exchange Rate}(A \to B), 2\right)$$

> **Important**: Transfer commission is always entered and calculated in **AED** (`CurrencyType::AED`).

The engine (`TransferCalculatorService`) provides two distinct calculation modes:

### Mode 1: Receiver Amount (`receiver_amount`)
Used when the sender specifies the exact net payout amount the recipient must receive in Gaza.
1. The operator enters: **Receiver Amount**, **Receiver Currency**, **Customer Payable Currency**, and **Commission (AED)**.
2. Converts receiver amount into the customer's payment currency.
3. Converts the commission from AED into the customer's payment currency.
4. Total Customer Payable:
   $$\text{Customer Payable Amount} = \text{Converted Receiver Amount} + \text{Converted Commission}$$

### Mode 2: Customer Payment (`customer_payment`)
Used when the sender provides a fixed lump sum (including fees) to be transferred.
1. The operator enters: **Customer Payment Amount**, **Customer Payment Currency**, **Receiver Currency**, and **Commission (AED)**.
2. Converts commission from AED into the customer's payment currency.
3. Validates that the commission does not exceed or equal the customer payment amount (throws validation exception if invalid).
4. Deducts commission from customer payment:
   $$\text{Net Principal} = \text{Customer Payment Amount} - \text{Converted Commission}$$
5. Converts the remaining net principal into the receiver's currency to determine the final payout amount:
   $$\text{Receiver Transfer Amount} = \text{Convert}(\text{Net Principal}, \text{Pay Currency} \to \text{Receiver Currency})$$

---

## Financial Management & Capital Accounting

The application implements a multi-currency double-entry ledger to track branch liquidity and commission revenue without relying on unverified third-party ledgers.

### Account Structure
- **Branches**: `UAE` (collection hub) and `GAZA` (disbursement hub).
- **Account Types**:
  - `capital`: Operating liquidity accounts holding sender deposits and disbursement reserves.
  - `profit`: Earnings accounts holding earned commissions (denominated in USD under the Gaza branch).

### Capital Transaction Types
Every account adjustment creates an immutable `CapitalTransaction` entry with `balance_before`, `balance_after`, `direction` (`IN` or `OUT`), creator ID, and polymorphic reference:
- `customer_transfer`: Inflow from sender payments or outflow for payout execution.
- `transfer_expense`: Commission deduction from disbursement capital.
- `transfer_cancellation`: Refund debit during pending transfer cancellation.
- `post_completion_cancellation`: Ledger-reversing entry when an admin force-cancels a completed transfer.
- `internal_transfer`: Inter-account rebalancing transfer.
- `manual_adjustment`: Authorized balance correction (`IN` / `OUT`) with mandatory audit notes.
- `profit_withdrawal`: Direct cash or bank withdrawal from a profit account.
- `profit_distribution`: Transfer of accumulated profit into an operational capital account.
- `opening_balance`: Initial capital balance allocation.

### Capital Transfers (Inter-Account Transfers)
- Allows moving funds between active capital accounts (e.g., rebalancing between branches or currencies).
- Automatically calculates exchange rates and destination amounts.
- Supports custom transfer cost fees, which are automatically debited from the Gaza Profit Account (`transfer_expense`).

### Financial Dashboard & Audit Ledger
- **Dashboard Overview**: Displays real-time Gaza Capital (USD), UAE Capital (USD & AED), Gaza Profit (USD), and total outstanding receivables across active transfers.
- **Capital Ledger (`/capital-ledger`)**: Admin-only comprehensive ledger view with filters by capital account, transaction type, and date range.

---

## User Roles & Permissions (RBAC)

Role-based access control is enforced via Laravel Gates defined in `AppServiceProvider`:

| Feature / Action | Admin | Coordinator | Executor | Transfer Executor |
| :--- | :---: | :---: | :---: | :---: |
| **View Transfers List & Details** | ✅ | ✅ | ✅ | ✅ |
| **Create & Edit Transfers** | ✅ | ✅ | ❌ | ❌ |
| **Receive Customer Payments** | ✅ | ✅ | ❌ | ❌ |
| **Cancel Pending Transfers** | ✅ | ✅ | ❌ | ❌ |
| **Execute Transfers & Upload Proof** | ✅ | ❌ | ✅ | ✅ |
| **Force-Cancel Completed Transfers** | ✅ | ❌ | ❌ | ❌ |
| **View Financial Dashboard** | ✅ | ✅ | ✅ | ❌ |
| **View Capital Accounts & Balances** | ✅ | ✅ | ✅ | ❌ |
| **Manage Accounts / Adjust Balances** | ✅ | ✅ | ✅ | ❌ |
| **Inter-Account Capital Transfers** | ✅ | ✅ | ❌ | ❌ |
| **Update Exchange Rates** | ✅ | ❌ | ✅ | ❌ |
| **View Capital Audit Ledger** | ✅ | ❌ | ❌ | ❌ |
| **User Management (CRUD, Passwords)** | ✅ | ❌ | ❌ | ❌ |

*(Note: The `admin` role possesses superadmin privileges via `Gate::before` and can execute all actions).*

---

## Telegram Bot Integration

The application integrates with the Telegram Bot API using Laravel queued listeners and incoming webhooks (`/telegram/webhook`).

### Verification & Account Linking
1. The user logs into the dashboard and clicks **Connect Telegram**.
2. The system generates a secure, single-use 6-character link code (valid for 10 minutes) stored in `users.telegram_link_code`.
3. The user sends `/link <CODE>` to the company Telegram bot.
4. The webhook verifies the code, stores the user's `telegram_chat_id`, enables notifications, and confirms the link in Telegram.

### Verified Event Notifications
Notifications are dispatched asynchronously through queued listeners (`ShouldQueue`):

1. **Transfer Created (`TransferCreated`)**:
   - Sent to: `admin`, `executor`, `transfer_executor` users with active Telegram links.
   - Message: Receiver name, payout amount & currency, payout method (bank/wallet), and creator name.
2. **Transfer Executed (`TransferExecuted`)**:
   - Sent to: `admin`, `executor`, `transfer_executor`, `coordinator` users.
   - Message: **Sends the uploaded receipt photo** with an HTML caption containing transfer reference number, recipient name, amount, method, and localized completion timestamp.
3. **Transfer Cancelled (`TransferCancelled`)**:
   - Sent to: `admin`, `executor`, `transfer_executor` users.
   - Message: Receiver name, amount, and cancellation alert.

*(Note: There are no notifications implemented for payment receipts or intermediate status edits).*

---

## Security & Session Management

- **Single Device Session Enforcement (`EnsureSingleDeviceSession` Middleware)**:
  - Users are restricted to one active session per device category (`mobile` vs `desktop`) using browser user-agent inspection.
  - Logging into an account on a second device of the same type immediately invalidates and logs out the previous session with an explanatory flash message.
- **Password-Protected Administrative Actions**: Force-cancellation of completed transfers requires re-entering the logged-in administrator's password.
- **Audit Trails**: All balance changes and transfer status transitions record user IDs (`created_by`, `completed_by`, `cancelled_by`, `received_by`).
- **Timezone Awareness**: Users can store their local timezone preference. Timestamps are persisted in UTC and converted dynamically for display using `LocalTime`.

---

## Versioned JSON API

The application provides a versioned JSON API for mobile and other first-party clients. The API reuses the application's transfer and capital services, applies the existing role permissions, and uses Laravel Sanctum bearer tokens. The full contract is available in [`docs/openapi.json`](docs/openapi.json).

### Authentication

1. Send `POST /api/v1/auth/login` with a JSON body containing `email`, `password`, and `device_name`.
2. Use the returned `access_token` on protected routes as `Authorization: Bearer <access_token>`.
3. Call `POST /api/v1/auth/logout` to revoke the current token. `GET /api/v1/me` returns the authenticated user's safe profile.

Tokens expire after 30 days. Login is limited to 5 attempts per minute. Inactive users cannot obtain or use API tokens. API token authentication is separate from the browser's single-device session flow.

Example login request:

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"your-password","device_name":"mobile-app"}'
```

### Endpoint groups

All endpoints are under `/api/v1`. Protected routes require a bearer token and are subject to role-based permissions.

| Group | Endpoints |
| :--- | :--- |
| Authentication | `POST /auth/login`, `POST /auth/logout`, `GET /me` |
| Transfers | `GET /transfers`, `POST /transfers/calculate`, `POST /transfers`, `GET /transfers/{transfer}`, `PATCH /transfers/{transfer}` |
| Transfer lifecycle | `POST /transfers/{transfer}/payments`, `/execute`, `/cancel`, and `/force-cancel` |
| Dashboard and rates | `GET /dashboard/summary`, `GET /exchange-rates`, `PUT /exchange-rates/{currency}` |
| Capital accounts | `GET` and `POST /capital-accounts`, `PATCH /capital-accounts/{account}`, account adjustments and profit withdrawals |
| Capital transfers and ledger | `POST /capital-transfers/preview`, `POST` and `GET /capital-transfers`, `GET /capital-transactions` |
| User administration | `GET` and `POST /users`, `GET` and `PATCH /users/{user}`, `PUT /users/{user}/password`, `DELETE /users/{user}` |

Transfer execution accepts a multipart `proof` upload (JPEG, PNG, or PDF, up to 2 MiB). Paginated lists accept `page` and `per_page`; page size is capped at 100.

### Idempotency and errors

Send an `Idempotency-Key` header on transfer creation, capital-account creation, payment receipt, capital adjustments, profit withdrawals, and capital transfers. Repeating the same request with the same key replays the saved response; reusing that key with a different payload returns `409`.

Validation failures return `422`. Domain conflicts return `409`; transfer calculations that cannot run because exchange rates are missing return `503`. Monetary response values are decimal strings. Force-cancelling a completed transfer requires an administrator password and non-empty audit notes.

Run the API feature tests with:

```bash
php artisan test tests/Feature/Api/V1
```

---

## Prerequisites & Local Installation

### System Requirements
- **PHP**: `^8.5` (with extensions: `pdo_mysql`, `mbstring`, `bcmath`, `gd`, `zip`, `pcntl`, `exif`)
- **Composer**: `v2.x`
- **Node.js**: `v20.x` or `v22.x` & **npm**
- **Database**: MySQL 8.0+

### Step-by-Step Local Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/Amjad-Alkahlout/transfer-management.git
   cd transfer-management
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Install frontend dependencies**:
   ```bash
   npm install
   ```

4. **Configure environment file**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Open `.env` and configure your database credentials (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

5. **Create the public storage symlink**:
   ```bash
   php artisan storage:link
   ```

6. **Run database migrations and seeders**:
   ```bash
   php artisan migrate --seed
   ```

7. **Compile frontend assets**:
   ```bash
   # For local development with hot reload:
   npm run dev

   # Or compile for production:
   npm run build
   ```

8. **Start the local development server and queue worker**:
   ```bash
   # Run the web server:
   php artisan serve

   # In a separate terminal, start the queue worker:
   php artisan queue:work
   ```
   *(Alternatively, run `composer run dev` to start server, queue worker, pail logs, and Vite concurrently).*

---

## Initial Post-Installation Step: Capital Account Setup

> [!IMPORTANT]
> The database seeder (`DatabaseSeeder`) seeds default users and initial exchange rates (USD, AED, ILS), **but does not automatically seed capital accounts**.  
> Because recording customer payments or executing transfers requires active capital accounts in the database, the **Administrator** must log in and manually create the following initial accounts under `/capital-accounts`:
> 
> 1. **UAE Branch**: Account Type `Capital`, Currency `USD` (and/or `AED`).
> 2. **Gaza Branch**: Account Type `Capital`, Currency `USD` (with sufficient opening balance to disburse transfers).
> 3. **Gaza Branch**: Account Type `Profit`, Currency `USD` (to receive commission profits).

### Default Seeded Users

All accounts are created by `UserSeeder` with the default password: `password`

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@example.com` | `password` |
| **Coordinator** | `coordinator@example.com` | `password` |
| **Executor** | `executor@example.com` | `password` |
| **Transfer Executor** | `transfer-executor@example.com` | `password` |

---

## Docker Environment Setup

The repository includes a multi-container Docker setup defined in `compose.yaml`:

- `transfer-app`: PHP 8.5-FPM container running application code.
- `transfer-queue-worker`: Identical PHP container running `php artisan queue:work --sleep=3 --tries=3`.
- `transfer-nginx`: Nginx 1.29 Alpine serving static assets and reverse proxying to PHP-FPM.
- `transfer-mysql`: MySQL 8.4 container with healthchecks.

### Important Docker Configuration Note
In `docker/nginx/default.conf` and `compose.yaml`, the included Nginx configuration is configured for the domain `transfer-hub.app` and expects SSL certificates at `/etc/letsencrypt/live/transfer-hub.app/fullchain.pem`, with port 80 redirecting to port 443.

For **local Docker testing without existing SSL certificates**, modify `docker/nginx/default.conf` to serve plain HTTP on port 80 and remove the `/etc/letsencrypt` volume mount in `compose.yaml` before running containers.

### Starting Docker Containers

```bash
# 1. Build and start containers
docker compose up -d --build

# 2. Check container status
docker compose ps

# 3. Run database migrations and seed default data
docker compose exec app php artisan migrate --seed
```

The application's entrypoint script (`docker-entrypoint.sh`) automatically waits for MySQL to be healthy, links public storage, clears/caches routes and config, and executes migrations on startup.

---

## Environment Configuration

Configure application parameters in `.env`. Never commit secrets or real credentials to version control.

| Variable | Default / Example | Description |
| :--- | :--- | :--- |
| `APP_NAME` | `MoneyTransferHub` | Application title |
| `APP_ENV` | `local` | `local` or `production` |
| `APP_KEY` | *(Generated by artisan)* | 32-character AES encryption key |
| `APP_DEBUG` | `true` | Show debug exceptions (`false` in production) |
| `APP_URL` | `http://localhost` | Canonical base URL |
| `DB_CONNECTION` | `mysql` | Database driver |
| `DB_HOST` | `127.0.0.1` (`mysql` in Docker) | Database hostname |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `money-transfer-management` | Database name |
| `DB_USERNAME` | `your_db_user` | Database user |
| `DB_PASSWORD` | `your_db_password` | Database password |
| `SESSION_DRIVER` | `database` | Session storage engine |
| `QUEUE_CONNECTION` | `database` | Queue storage engine |
| `FILESYSTEM_DISK` | `public` | Storage disk for transfer receipt uploads |
| `TELEGRAM_BOT_TOKEN` | `your_bot_token_here` | Telegram Bot API authentication token |

---

## Running Queues, Seeders & Tests

### Database Seeding
To reset and seed the database with test users and base exchange rates:
```bash
php artisan migrate:fresh --seed
```

### Running Background Queues
The application requires a running queue worker to dispatch Telegram notifications:
```bash
php artisan queue:work --tries=3 --timeout=60
```

### Running Tests
The project uses PHPUnit / Laravel test runner:
```bash
php artisan test
```
> [!NOTE]
> The API has feature tests for authentication, permissions, transfer lifecycle operations, idempotency, capital accounting, user administration, and the OpenAPI contract. Broader unit and UI coverage for the existing domain services and Livewire workflows remains a work in progress.

---

## Deployment Verification

- **Docker Compose**: Containerized multi-service deployment (`app`, `queue-worker`, `nginx`, `mysql`) is verified by the included Dockerfiles and entrypoint scripts.
- **Nginx Reverse Proxy & Let's Encrypt**: Verified in `docker/nginx/default.conf` and `compose.yaml` configured for `transfer-hub.app`.
- **Cloud Infrastructure Claims**: Past documentation referenced deployment on Microsoft Azure Virtual Machines. However, no Azure-specific Infrastructure as Code (ARM templates, Bicep, Terraform) or CI/CD deployment pipelines exist in this repository.

---

## Current Limitations & Planned Features

### Current Limitations
- **Educational / Portfolio Scope**: This is an educational and portfolio system demonstrating financial workflows; it is not verified or licensed as production-grade banking software.
- **Corridor Fixed to UAE & Gaza**: Capital account logic in `ReceivePaymentService` and `TransferExecutionService` expects payments to route to UAE Capital and disbursements/profits to deduct from Gaza Capital. Corridors cannot be dynamically reconfigured without modifying service code.
- **Manual Account Initialization**: As noted, capital accounts are not seeded by default and must be manually configured before transfers can be executed.
- **Fixed Commission Currency**: Transfer commission rules are fixed in AED across all transfers.
- **Fixed Currency Enum**: Currencies are constrained to `USD`, `AED`, and `ILS` via PHP enums; adding additional currencies requires migration and enum adjustments.
- **Test Coverage**: API workflows have feature coverage; broader unit and Livewire UI coverage remains incomplete.
- **Receipts**: Payout proofs can be uploaded, but PDF receipt generation for customers is not yet supported.

### Planned Features
- [ ] Comprehensive unit and feature test coverage for calculation and capital services.
- [ ] Dynamic multi-corridor and multi-branch configuration from the UI.
- [ ] Exportable PDF customer receipts and disbursement vouchers.
- [ ] Comprehensive system audit logging for non-capital actions.
- [ ] Redis cache and queue driver support.

---

## Author & License

- **Author**: [Amjad Alkahloot](https://github.com/Amjad-Alkahlout)  
  Computer Science Engineering Student, Andhra University
- **License**: Developed for educational and portfolio purposes.
