# Barangay Planning & Scheduling System (PSS)

A web-based planning and scheduling system for barangay (Philippine local government unit) administration. It helps barangay officials organize events, manage meeting minutes, send SMS notifications to constituents, and maintain records of officials and contacts.

---

## Features

- **Event & Agenda Management** — Schedule barangay events with detailed agendas and track agenda item statuses (pending, done, deferred, cancelled)
- **Calendar View** — Visual calendar interface for browsing scheduled events
- **SMS Notifications** — Automated SMS blasts to contacts/groups via FMCSMS API; supports queue processing and resend
- **Meeting Minutes** — Upload, manage, and AI-extract (Google Gemini) structured data from minutes documents
- **Contact Management** — Maintain a contact directory organized into groups for targeted SMS
- **Officials Directory** — Manage barangay officials with e-signature support
- **Reports & Analytics** — Event completion summaries and scheduling statistics
- **User Accounts** — Role-based access control (admin / staff) with activity audit logging
- **Remember Me** — Persistent login sessions with expiring tokens

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2+, PDO (MySQL) |
| Database | MySQL / MariaDB |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Vanilla JS |
| Calendar | FullCalendar 6.1 |
| AI | Google Gemini API (minutes extraction) |
| SMS | FMCSMS API |
| Image | Remove.bg API (background removal) |
| Server | Apache via XAMPP |

No Composer or npm — all dependencies are CDN-delivered.

---

## Project Structure

```
capstone/
├── index.php                        # Main router / entry point
├── .env.example                     # Environment variable template
│
├── backend/
│   ├── auth/                        # API endpoint files (PHP)
│   ├── config/
│   │   ├── conn.php                 # PDO database connection
│   │   ├── env.php                  # .env file loader
│   │   ├── gemini.php               # Google Gemini config
│   │   ├── removebg.php             # Remove.bg config
│   │   └── sms.php                  # FMCSMS config & helper
│   └── database/
│       ├── pss_db.sql               # Full schema + seed data
│       └── migrations/              # Incremental migration scripts
│
├── frontend/
│   ├── config/routes.php            # Application routing
│   ├── pages/
│   │   ├── public/                  # Unauthenticated pages (landing, login)
│   │   └── private/                 # Authenticated pages
│   ├── includes/                    # Shared layout partials & modals
│   ├── css/                         # Stylesheets
│   ├── js/                          # JavaScript modules
│   └── assets/images/               # Static images / logos
│
└── uploads/                         # User-uploaded files (git-ignored)
```

---

## Getting Started

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) (Apache + PHP 8.2+ + MySQL)
- Git

### Installation

1. **Clone the repository** into your XAMPP `htdocs` folder:

   ```bash
   git clone https://github.com/your-username/your-repo.git C:/xampp/htdocs/capstone
   ```

2. **Import the database:**

   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a database named `pss_db`
   - Import `backend/database/pss_db.sql`
   - Then apply any migration scripts in `backend/database/migrations/` in order

3. **Configure environment variables:**

   ```bash
   cp .env.example .env
   ```

   Edit `.env` with your values:

   ```env
   DB_HOST=localhost
   DB_NAME=pss_db
   DB_USER=root
   DB_PASS=

   GEMINI_API_KEY=your_gemini_api_key_here
   GEMINI_MODEL=gemini-2.0-flash

   FMCSMS_API_URL=https://fortmed.org/web/FMCSMS/api/messages.php
   FMCSMS_API_KEY=your_fmcsms_api_key_here
   FMCSMS_SENDER_NAME=Your System
   FMCSMS_FROM_NUMBER=+639171234567

   REMOVEBG_API_KEY=your_removebg_api_key_here
   ```

4. **Start XAMPP** (Apache + MySQL) and navigate to:

   ```
   http://localhost/capstone
   ```

### Default Credentials

| Role | Username | Password |
|---|---|---|
| Admin | `delven1` | *(set in database)* |
| Staff | `staff` | *(set in database)* |

---

## Environment Variables

See [.env.example](.env.example) for the full list. The `.env` file is **git-ignored** and must never be committed.

| Variable | Description |
|---|---|
| `DB_HOST` | Database host |
| `DB_NAME` | Database name |
| `DB_USER` | Database user |
| `DB_PASS` | Database password |
| `GEMINI_API_KEY` | Google Gemini API key for AI minutes extraction |
| `GEMINI_MODEL` | Gemini model ID (e.g. `gemini-2.0-flash`) |
| `FMCSMS_API_URL` | FMCSMS message endpoint |
| `FMCSMS_API_KEY` | FMCSMS API key for SMS sending |
| `FMCSMS_SENDER_NAME` | Sender name sent to the FMCSMS API |
| `FMCSMS_FROM_NUMBER` | Source mobile number sent to the FMCSMS API |
| `REMOVEBG_API_KEY` | Remove.bg API key for image background removal |

---

## Database Migrations

After the initial import, apply migrations in numeric order:

```bash
# Via phpMyAdmin or MySQL CLI
002_sms_outbox_allow_multi_send.sql
003_users_remember_token.sql
```

---

## Security Notes

- All database queries use **PDO prepared statements** (SQL injection prevention)
- Passwords are hashed with **bcrypt**
- Authentication is **session-based** with optional remember-me tokens
- Role-based access control enforced on all private pages and API endpoints
- All user actions are recorded in the **activity log**
