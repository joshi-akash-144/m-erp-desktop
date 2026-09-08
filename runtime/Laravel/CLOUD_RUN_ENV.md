# ☁️ Cloud Run — Environment Variables Reference

All keys you must set in **Cloud Run → Edit & Deploy → Variables & Secrets**.

---

## 🔴 REQUIRED (App will not boot without these)

| Key | Example Value | Notes |
|-----|--------------|-------|
| `APP_KEY` | `base64:xxxxxxxxxxxxxxxx=` | Generate with `php artisan key:generate --show` locally |
| `APP_ENV` | `production` | Never `local` on Cloud Run |
| `APP_DEBUG` | `false` | **Must be false** in production |
| `APP_URL` | `https://your-service-abc123-uc.a.run.app` | Your full Cloud Run service URL |

---

## 🗄️ Database (MySQL / Cloud SQL)

### Option A — Cloud SQL via Unix Socket (Recommended for Cloud Run)

| Key | Example Value | Notes |
|-----|--------------|-------|
| `DB_CONNECTION` | `mysql` | |
| `DB_DATABASE` | `erp_db` | Your database name |
| `DB_USERNAME` | `erp_user` | |
| `DB_PASSWORD` | `your_password` | Use Secret Manager for this |
| `DB_SOCKET` | `/cloudsql/project:region:instance` | Cloud SQL connection name |

### Option B — TCP Host (e.g. external MySQL)

| Key | Example Value | Notes |
|-----|--------------|-------|
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` | `34.xx.xx.xx` | Public IP of your DB |
| `DB_PORT` | `3306` | |
| `DB_DATABASE` | `erp_db` | |
| `DB_USERNAME` | `erp_user` | |
| `DB_PASSWORD` | `your_password` | |

---

## 📧 Mail (if app sends emails)

| Key | Example Value | Notes |
|-----|--------------|-------|
| `MAIL_MAILER` | `smtp` | Use `log` to disable sending |
| `MAIL_HOST` | `smtp.gmail.com` | |
| `MAIL_PORT` | `587` | |
| `MAIL_USERNAME` | `you@gmail.com` | |
| `MAIL_PASSWORD` | `app_password` | Use Secret Manager |
| `MAIL_SCHEME` | `tls` | |
| `MAIL_FROM_ADDRESS` | `noreply@yourcompany.com` | |
| `MAIL_FROM_NAME` | `M-ERP` | |

---

## 🗂️ Session & Cache

| Key | Value | Notes |
|-----|-------|-------|
| `SESSION_DRIVER` | `database` | Safe for Cloud Run (stateless containers) |
| `SESSION_LIFETIME` | `120` | Minutes |
| `CACHE_STORE` | `database` | Safe — no Redis needed |
| `QUEUE_CONNECTION` | `database` | Safe for Cloud Run |

> **Note:** If you add Redis later, change these to `redis` and add `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`.

---

## 🚀 Deployment Control

| Key | Default | Notes |
|-----|---------|-------|
| `RUN_MIGRATIONS` | `false` | Set to `true` only when deploying a new migration. Reset to `false` after. |
| `PORT` | `8080` | Set automatically by Cloud Run — do **not** set manually |

---

## 🔵 Optional but Recommended

| Key | Value | Notes |
|-----|-------|-------|
| `APP_NAME` | `M-ERP` | Shown in emails, UI |
| `APP_TIMEZONE` | `Asia/Kolkata` | Set to your timezone |
| `APP_LOCALE` | `en` | |
| `LOG_CHANNEL` | `stderr` | Best for Cloud Run — logs go to Cloud Logging |
| `LOG_LEVEL` | `error` | Use `error` in production (not `debug`) |
| `BCRYPT_ROUNDS` | `12` | |

---

## 🔐 Use Secret Manager for These

Never paste these as plain text in Cloud Run variables:

- `APP_KEY`
- `DB_PASSWORD`
- `MAIL_PASSWORD`

In Cloud Run → use **"Reference a Secret"** instead of a plain variable.

---

## ✅ Minimum Keys Checklist

```env
APP_KEY=base64:...
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-url.run.app

DB_CONNECTION=mysql
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass
DB_SOCKET=/cloudsql/project:region:instance   # OR DB_HOST + DB_PORT

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stderr
LOG_LEVEL=error

MAIL_MAILER=log   # or smtp with full mail config above

RUN_MIGRATIONS=false
```
