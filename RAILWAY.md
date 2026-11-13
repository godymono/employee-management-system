# Deploying PeoplePulse on Railway

## What this setup does

- Builds PHP (8.4) + Composer dependencies via Nixpacks
- Builds frontend assets with Vite (`npm ci` + `npm run build`)
- Serves Laravel from `public/` with Nginx + PHP-FPM
- Runs migrations (and optional seed) on each release via `railway/release.sh`

## 1. Create the project on Railway

1. Open [Railway](https://railway.app) and create a **New Project**.
2. Add a **PostgreSQL** plugin (recommended) *or* MySQL.
3. Add a service from the GitHub repo: `godymono/employee-management-system`.

## 2. Required environment variables

Set these on the **web service** (not the database plugin):

| Variable | Value |
|---|---|
| `APP_NAME` | `PeoplePulse` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate with `php artisan key:generate --show` |
| `APP_URL` | Your Railway public URL (e.g. `https://….up.railway.app`) |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |
| `DB_CONNECTION` | `pgsql` (or `mysql`) |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `local` |
| `SEED_ON_DEPLOY` | `true` on first deploy, then `false` |

### Database connection

**Easiest:** reference the Railway database variables:

- If using Postgres plugin, set `DATABASE_URL` / `DB_URL` from `${{Postgres.DATABASE_URL}}`
- Also set `DB_CONNECTION=pgsql`

Example variable references in Railway UI:

```text
DB_URL=${{Postgres.DATABASE_URL}}
DB_CONNECTION=pgsql
```

(Replace `Postgres` with your plugin’s service name if different.)

## 3. First deploy checklist

1. Generate and set `APP_KEY`.
2. Set `SEED_ON_DEPLOY=true` once so demo users/roles are created.
3. Deploy.
4. After a successful first deploy, set `SEED_ON_DEPLOY=false`.
5. Update `APP_URL` to the final public domain.

### Demo logins (after seed)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@peoplepulse.test` | `password` |
| HR | `hr@peoplepulse.test` | `password` |
| Manager | `manager@peoplepulse.test` | `password` |
| Employee | `employee@peoplepulse.test` | `password` |

Change these passwords immediately in production.

## 4. CLI deploy (optional)

```bash
npm i -g @railway/cli
railway login
railway link
railway add --database postgres   # if not already added
railway variables set APP_KEY=base64:... APP_ENV=production ...
railway up
```

## Notes

- Uploaded photos live on the container filesystem and are **ephemeral** unless you attach a volume or S3. For production uploads, switch `FILESYSTEM_DISK` to S3-compatible storage.
- Health check endpoint: `/up`
