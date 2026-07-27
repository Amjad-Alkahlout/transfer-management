# 💸 Money Transfer Management System

A production-ready Money Transfer Management System built with Laravel, Livewire, Docker, and Telegram integration.

The platform manages the complete lifecycle of international money transfers, from transfer creation to payment collection, execution, accounting, and real-time notifications.

---

# ✨ Features

## Authentication & Authorization

- Secure authentication
- Role-based access control
- Permission management
- Protected routes
- Session management

---

## Transfer Management

- Create and edit money transfers
- Manual commission entry (AED)
- Automatic multi-currency calculations
- Receiver Amount calculation mode
- Customer Payment calculation mode
- Payment collection
- Transfer execution
- Transfer cancellation
- Transfer history
- Status tracking

---

## Financial Management

### Capital Accounts

- Manage multiple capital accounts
- Track balances
- View account transactions

### Exchange Rates

- Manage exchange rates
- Automatic currency conversion
- Multi-currency support
- Real-time transfer calculations

---

## Dashboard

Interactive dashboard displaying:

- Capital balances
- Outstanding receivables
- Transfer statistics
- Payment overview
- Quick actions

---

# 💱 Transfer Calculation

The system supports two calculation modes.

## Receiver Amount

The operator enters:

- Receiver amount
- Receiver currency
- Customer payment currency
- Commission (AED)

The system automatically:

- Converts the receiver amount into the customer's payment currency.
- Converts the commission from AED into the customer's payment currency.
- Adds the commission.
- Calculates the final customer payable amount.

---

## Customer Payment

The operator enters:

- Customer payment amount
- Customer payment currency
- Receiver currency
- Commission (AED)

The system automatically:

- Converts the commission from AED into the payment currency.
- Deducts the commission.
- Converts the remaining amount into the receiver's currency.
- Calculates the final transfer amount.

---

## Telegram Integration

Users can securely connect their Telegram account.

Features include:

- Telegram account linking
- Verification code
- Webhook integration
- Automatic Chat ID registration
- Queue-based notifications

Notifications include:

- Transfer created
- Payment received
- Transfer executed
- Workflow updates

---

## Queue System

Background jobs are processed using Laravel Queues.

Dedicated Queue Worker handles:

- Telegram notifications
- Background jobs
- Event listeners

---

# 🏗️ Architecture

```
                +----------------+
                |     Nginx      |
                +--------+-------+
                         |
                         |
                +--------v-------+
                | Laravel App    |
                +--------+-------+
                         |
          +--------------+--------------+
          |                             |
  +-------v------+             +--------v-------+
  | Queue Worker |             |     MySQL      |
  +--------------+             +----------------+
```

---

# 🛠️ Tech Stack

## Backend

- Laravel 12
- PHP 8.4
- Livewire
- Eloquent ORM
- Service Layer Architecture
- Laravel Policies & Gates

## Frontend

- Blade
- Livewire
- Tailwind CSS
- Alpine.js
- Vite

## Database

- MySQL 8.4

## Infrastructure

- Docker
- Docker Compose
- Nginx

## Notifications

- Telegram Bot API
- Webhooks
- Laravel Queues

## Deployment

- Microsoft Azure VM
- Docker
- Nginx
- Let's Encrypt SSL

---

# 📂 Project Structure

```
app/
bootstrap/
config/
database/
docker/
public/
resources/
routes/
storage/
```

---

# ⚙️ Installation

Clone the repository

```bash
git clone https://github.com/Amjad-Alkahlout/transfer-management.git
```

Enter the project

```bash
cd transfer-management
```

Copy the environment file

```bash
cp .env.example .env
```

Generate the application key

```bash
php artisan key:generate
```

---

# 🐳 Docker

Build containers

```bash
docker compose build
```

Start containers

```bash
docker compose up -d
```

Run migrations

```bash
docker compose exec app php artisan migrate
```

Seed the database

```bash
docker compose exec app php artisan db:seed
```

Optimize the application

```bash
docker compose exec app php artisan optimize
```

---

# 🤖 Telegram Setup

Configure:

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=
```

Register the webhook

```text
https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/telegram/webhook
```

---

# 👥 User Roles

The system supports multiple roles.

Examples include:

- Administrator
- Coordinator
- Executor

Each role has dedicated permissions and responsibilities.

---

# 🔄 Transfer Workflow

```
Transfer Created
        │
        ▼
Payment Received
        │
        ▼
Transfer Executed
        │
        ▼
Completed
```

---

# 📬 Queue Processing

Run the queue worker

```bash
php artisan queue:work
```

Used for:

- Telegram notifications
- Background jobs
- Event listeners

---

# 📸 Screenshots

Screenshots will be added soon.

Suggested images:

```
screenshots/

dashboard.png
transfer-create.png
transfer-details.png
exchange-rates.png
telegram.png
```

---

# 🚀 Future Improvements

- REST API
- Flutter Mobile Application
- Push Notifications
- PDF Receipts
- Audit Logs
- Redis Queues
- Real-time Dashboard
- Cloud Storage Integration

---

# 👨‍💻 Author

**Amjad Alkahloot**

Computer Science Engineering Student

Andhra University

GitHub

https://github.com/Amjad-Alkahlout

---

# 📄 License

This project was developed for educational and portfolio purposes.
