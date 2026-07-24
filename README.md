# 💸 Money Transfer Management System

A production-ready money transfer management platform built with Laravel, Livewire, Docker, and Telegram integration.

The system manages the complete lifecycle of international money transfers, including pricing, approvals, execution, accounting, and real-time Telegram notifications.

---

# ✨ Features

## Authentication & Authorization

- Secure user authentication
- Role-based access control
- Protected routes and permissions
- Session management

---

## Transfer Management

- Create money transfers
- Review transfer details
- Pricing workflow
- Approval process
- Execution tracking
- Transfer history
- Transfer status management

---

## Financial Management

### Capital Accounts

- Multiple capital accounts
- Current balances
- Transaction history

### Exchange Rates

- Manage exchange rates
- Currency conversion
- Live pricing support

### Commission Rules

- Flexible commission configuration
- Multiple commission types
- Automatic commission calculation

---

## Dashboard

Interactive dashboard displaying:

- Capital balances
- Outstanding receivables
- Transfer statistics
- Payment overview
- Quick actions

---

## Telegram Integration

Users can securely connect their Telegram account to receive notifications.

### Features

- Telegram account linking
- Secure verification code
- Telegram Webhook
- Automatic chat ID registration
- Queue-based notification delivery

Notifications include:

- Transfer created
- Approval updates
- Execution updates
- Other workflow events

---

## Queue System

Background jobs are processed using Laravel Queues.

Dedicated Queue Worker container:

- Notification processing
- Event listeners
- Background tasks

---

## Dockerized Production Environment

The application runs using Docker Compose with separate containers.

```
                +----------------+
                |    Nginx       |
                +--------+-------+
                         |
                         |
                +--------v-------+
                | Laravel App    |
                +--------+-------+
                         |
             +-----------+-----------+
             |                       |
     +-------v------+       +--------v-------+
     | Queue Worker |       |     MySQL      |
     +--------------+       +----------------+
```

---

# 🏗 Tech Stack

### Backend

- Laravel
- PHP 8.4
- Livewire
- Eloquent ORM

### Frontend

- Blade
- Livewire
- Tailwind CSS
- Vite

### Database

- MySQL 8.4

### Infrastructure

- Docker
- Docker Compose
- Nginx

### Notifications

- Telegram Bot API
- Webhooks
- Laravel Queues

### Deployment

- Microsoft Azure VM
- Let's Encrypt SSL
- Custom Domain

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

Copy environment file

```bash
cp .env.example .env
```

Generate application key

```bash
php artisan key:generate
```

---

# 🐳 Docker

Build containers

```bash
docker compose build
```

Run containers

```bash
docker compose up -d
```

Run migrations

```bash
docker compose exec app php artisan migrate
```

Seed database

```bash
docker compose exec app php artisan db:seed
```

---

# 🤖 Telegram Setup

Configure the following environment variables:

```env
TELEGRAM_BOT_TOKEN=
TELEGRAM_BOT_USERNAME=
```

Register the webhook

```bash
https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/telegram/webhook
```

Users can then link their Telegram account directly from the application.

---

# 🔐 HTTPS

Production deployment includes

- Let's Encrypt SSL
- Automatic certificate renewal
- HTTPS enforcement

---

# 🚀 Production Deployment

The application is deployed using

- Azure Virtual Machine
- Docker Compose
- Nginx Reverse Proxy
- MySQL
- Queue Worker
- HTTPS

---

# 👥 User Roles

The system supports multiple roles throughout the transfer workflow.

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
Pricing
        │
        ▼
Approval
        │
        ▼
Execution
        │
        ▼
Accounting
        │
        ▼
Completed
```

Telegram notifications are automatically sent during important workflow events.

---

# 📬 Queue Processing

Queue Worker runs continuously inside its own Docker container.

```bash
php artisan queue:work
```

Used for

- Telegram notifications
- Background jobs
- Event listeners

---

# 📸 Screenshots

Add screenshots here.

Example:

```
screenshots/

dashboard.png

transfers.png

telegram.png
```

---

# 🔮 Future Improvements

- Email notifications
- PDF receipts
- Multi-language support
- Audit logs
- API integration
- Redis queue
- Real-time dashboard
- Cloud storage support

---

# 👨‍💻 Author

**Amjad Alkahlout**

Computer Science Engineering Student

Andhra University

GitHub:

https://github.com/Amjad-Alkahlout

---

# 📄 License

This project was developed for educational and portfolio purposes.
