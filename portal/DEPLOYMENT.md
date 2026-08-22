# Deployment & Operations — NIMR GMU Portal

Operational notes for running this application. Written during Phase 0 remediation
(22 August 2026); keep it current as the environment changes.

---

## 1. Web server

The application must only ever expose `portal/public`. Everything above it —
`.env`, `composer.json`, `storage/logs`, the whole source tree — must be
unreachable over HTTP.

### Current state (interim)

The app lives inside the XAMPP document root, so Apache serves the project
directory directly. Access is blocked with `.htaccess` files:

| File | Purpose |
|---|---|
| `portal/.htaccess` | `Require all denied` — blocks the entire application directory |
| `portal/public/.htaccess` | `Require all granted` — re-opens only the served directory |
| `portal/storage/app/public/.htaccess` | Denies direct access to uploads and strips script handlers |

Verify after any Apache change:

```bash
curl -o /dev/null -w "%{http_code}\n" http://localhost/GMU/portal/.env          # expect 403
curl -o /dev/null -w "%{http_code}\n" http://localhost/GMU/portal/composer.json  # expect 403
curl -o /dev/null -w "%{http_code}\n" http://localhost/GMU/portal/public/login   # expect 200
```

These rules depend on `AllowOverride All` being set for the document root. If
that is ever tightened, the deny rules stop applying and the source is exposed
again — so re-run the checks above after touching Apache configuration.

### Permanent fix (do this before go-live)

Give the application its own virtual host whose `DocumentRoot` is `public`, so
nothing above it is reachable regardless of `.htaccess`:

```apache
# C:\xampp\apache\conf\extra\httpd-vhosts.conf
<VirtualHost *:80>
    ServerName  gmu.nimr.local
    DocumentRoot "C:/xampp/htdocs/GMU/portal/public"

    <Directory "C:/xampp/htdocs/GMU/portal/public">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog  "logs/gmu-error.log"
    CustomLog "logs/gmu-access.log" combined
</VirtualHost>
```

Add `127.0.0.1 gmu.nimr.local` to `C:\Windows\System32\drivers\etc\hosts`,
set `APP_URL=http://gmu.nimr.local` in `.env`, run `php artisan config:clear`,
and restart Apache. For production, terminate TLS and set
`SESSION_SECURE_COOKIE=true`.

---

## 2. Database

The application connects as `gmu_app@localhost`, which holds privileges on the
`nimr_gmu` schema only — no access to the other databases on this instance, and
no `GRANT`, `FILE` or `SUPER`. The password is in `.env` (not in version
control). Never point the app back at `root`.

To rotate the password:

```sql
ALTER USER 'gmu_app'@'localhost' IDENTIFIED BY '<new password>';
```

then update `DB_PASSWORD` in `.env` and run `php artisan config:clear`.

### MariaDB incident — 22 August 2026

While creating the application user, `GRANT` failed: the Aria system table
`mysql.db` was corrupt and the Aria log file could not be written. A clean
shutdown hung, the process was terminated, the Aria log files were moved aside
(kept in `C:\gmu-backups\aria-logs-20260822\`), and MariaDB rebuilt them on
restart, self-repairing `mysql.db`, `mysql.global_priv` and `mysql.proxies_priv`.
All 16 databases on the instance came back intact and were verified.

Two things still need attention:

- The error log shows recurring InnoDB `log sequence number is in the future`
  warnings, meaning the InnoDB tablespace and its logs disagree — usually the
  result of a data directory copied without its log files, or an unclean
  shutdown. It is not currently fatal, but this instance should be treated as
  fragile until it is rebuilt (dump everything, reinstall MariaDB, reimport).
- Database-level privilege rows were lost during the repair and were recreated
  for `gmu_app` and `pma`. If another application on this machine used a
  database-scoped grant, it will need re-granting.

---

## 3. Backups

`C:\gmu-backups\backup-gmu.ps1` dumps the database, copies `storage/app`, and
prunes to 30 days. It is registered as the scheduled task
`NIMR-GMU-Nightly-Backup`, running daily at 01:00, and logs to
`C:\gmu-backups\backup.log`.

Run on demand:

```powershell
powershell -ExecutionPolicy Bypass -File C:\gmu-backups\backup-gmu.ps1
```

**These copies sit on the same disk as the database.** Copy `C:\gmu-backups`
to another machine or drive on a regular schedule — a disk failure otherwise
takes the database and every backup with it.

Restore:

```bash
C:/xampp/mysql/bin/mysql -u root < C:/gmu-backups/nimr_gmu-<timestamp>.sql
```

Test a restore into a scratch database at least once a quarter. A backup that
has never been restored is not yet a backup.

---

## 4. Accounts

Self-registration is disabled. Accounts are created by the Coordinator at
`/admin/users`. The seeder's default administrator password was rotated on
22 August 2026; new passwords were handed over out of band and should be
changed at first login.

The seeder still contains a hardcoded password for fresh installs — moving it
to an environment variable is a Phase 1 task (audit finding H4).

---

## 5. Scheduler

Deadline reminders and the weekly digest depend on Laravel's scheduler running
every minute. On Windows, register a scheduled task:

```
schtasks /Create /TN "NIMR-GMU-Scheduler" /SC MINUTE /MO 1 ^
  /TR "C:\xampp\php\php.exe C:\xampp\htdocs\GMU\portal\artisan schedule:run" /F
```

Note that `MAIL_MAILER` is currently `log`, so no mail leaves the machine.
Set real SMTP credentials and send one test digest before relying on reminders
(audit finding H8).

---

## 6. Release checklist

- [ ] `php artisan config:clear && php artisan route:clear && php artisan view:clear`
- [ ] `npm run build` if front-end assets changed
- [ ] `php artisan migrate --force`
- [ ] Backup taken before migrating
- [ ] Exposure checks in §1 re-run
- [ ] `APP_DEBUG=false` and `APP_ENV=production` in any shared environment
