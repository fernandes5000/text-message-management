# Text Message Management

A full-featured, multi-tenant text message management platform for organizations of any kind. Built as a demo system — messages are simulated via email (Mailhog) instead of real SMS delivery.

> **DEMO MODE**: This application does not send real SMS messages. All outbound messages are delivered as emails through Mailhog for demonstration purposes.

---

## Features

- **Dashboard** — Account performance charts (subscribers, opt-ins, opt-outs, outgoing/incoming texts) with period filters (1W, 1M, 6M, 1Y)
- **Subscribers** — Full CRUD, CSV/Excel import, opt-in/opt-out tracking
- **Lists** — Subscriber grouping with manual or integration-synced lists
- **Messages** — Compose and send text messages (simulated via email), schedule for later, set recurring sends, attach images/videos/polls
- **Keywords** — Auto-enrollment workflows triggered by inbound keywords, with aliases and multi-step automation
- **Inbox** — Real-time two-way conversation management powered by WebSockets (Laravel Reverb)
- **Polls** — Create polls and attach them to messages
- **Integrations** — Simulated third-party integrations (Planning Center, Salesforce, Zapier)
- **Multi-account** — Sub-accounts with independent subscriber bases and messaging; switch between accounts from the header
- **Account Settings** — Edit organization name and default number, view credits, manage team members (invite, role badges, remove)
- **Internationalization** — English (default), Brazilian Portuguese, and Spanish
- **Dark / Light mode** — User-toggleable theme, persisted in localStorage
- **Responsive layout** — Mobile-friendly sidebar with hamburger drawer, adaptive header controls

---

## Tech Stack

### Backend

| Technology | Version |
|---|---|
| PHP | 8.4 |
| Laravel | 12 |
| Laravel Reverb | latest (WebSockets) |
| Laravel Sanctum | latest (API authentication) |
| MySQL | 8.0 |
| Redis | 7 |
| Mailhog | latest (SMTP simulator) |

### Frontend

| Technology | Version |
|---|---|
| Vue.js | 3.x |
| TypeScript | 5.x |
| Tailwind CSS | v3 |
| Vite | 5.x |
| Vue Router | 4.x |
| Pinia | 2.x |
| Vue I18n | 9.x |
| Chart.js | 4.x |
| vue-chartjs | 5.x |

All UI components are built manually with Tailwind CSS — no third-party component library.

### Infrastructure

| Service | Image | Port |
|---|---|---|
| App (PHP-FPM) | php:8.4-fpm (custom) | — |
| Web server | nginx:alpine | 8080 |
| Database | mysql:8.0 | 3306 |
| Cache / Queue | redis:7-alpine | 6379 |
| Mail (SMTP simulator) | mailhog/mailhog | 8025 (UI), 1025 (SMTP) |

### Testing

| Tool | Purpose |
|---|---|
| Pest PHP | Backend unit and feature tests |
| Vitest | Frontend component and store tests |

---

## Getting Started

### Prerequisites

- Docker and Docker Compose v2
- Git

### Installation

```bash
# Clone the repository
git clone git@github.com:fernandes5000/text-message-management.git
cd text-message-management

# Copy environment file
cp .env.example .env

# Start all containers
docker compose up -d

# Install PHP dependencies
docker compose exec app composer install

# Generate application key
docker compose exec app php artisan key:generate

# Run migrations and seed the database
docker compose exec app php artisan migrate --seed

# Install Node dependencies and build frontend
docker compose exec app npm install
docker compose exec app npm run dev
```

### Access

| Service | URL |
|---|---|
| Application | http://localhost:8080 |
| Mailhog (email viewer) | http://localhost:8025 |

### Demo Credentials

```
Email:    demo@textmessage.dev
Password: password
```

---

## Project Structure

```
.
├── app/
│   ├── Events/             # Reverb broadcast events
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   ├── Requests/
│   │   └── Resources/      # JSON API resources
│   ├── Jobs/               # Queue jobs (message sending)
│   ├── Models/
│   ├── Policies/
│   └── Services/           # Business logic layer
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docker/
│   ├── nginx/
│   └── php/
├── resources/
│   └── js/                 # Vue 3 SPA
│       ├── components/
│       │   └── ui/         # Base UI components (Button, Input, Modal, etc.)
│       ├── composables/
│       ├── layouts/
│       ├── locales/        # en.json, pt-BR.json, es.json
│       ├── pages/
│       ├── router/
│       ├── stores/         # Pinia stores
│       └── types/
├── routes/
│   ├── api.php
│   ├── channels.php        # Reverb broadcast channels
│   └── web.php
└── tests/
    ├── Feature/
    └── Unit/
```

---

## Running Tests

```bash
# Backend tests (Pest PHP)
docker compose exec app php artisan test

# Backend tests with coverage
docker compose exec app php artisan test --coverage

# Frontend tests (Vitest)
docker compose exec app npm run test
```

---

## Seeded Data

The database seeder populates realistic demo data:

- **~4,500 subscribers** with opt-in/opt-out history
- **6–10 lists** per organization (Volunteers, Attended in last 6mo, Parents of Youth, etc.)
- **~15 keywords** (active and archived) with multi-step workflows
- **~50 messages** (sent, scheduled, and draft)
- **~20 inbox conversations** with inbound and outbound messages per organization
- **5 polls** per organization with realistic response distributions
- **5 mock integrations** per organization (Planning Center, Salesforce, Zapier, Mailchimp, HubSpot)
- **6 sub-accounts** (College Ministry, Easthill Campus, Guest Assimilation, Jordan's Ministry, Main Campus, Pastor Stephen)

---

## Environment Variables

Key variables in `.env`:

```env
APP_URL=http://localhost:8080

DB_HOST=mysql
DB_DATABASE=tmm
DB_USERNAME=tmm
DB_PASSWORD=secret

REDIS_HOST=redis

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

BROADCAST_DRIVER=reverb
REVERB_APP_ID=tmm
REVERB_APP_KEY=tmm-key
REVERB_APP_SECRET=tmm-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

---

## License

MIT
