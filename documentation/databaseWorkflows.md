# NIWFF database workflows — staging setup

## Hosting and prerequisites

Deploy by Git pull into the current staging root. Requires PHP 8.1+ with PDO MySQL, MariaDB/MySQL, HTTPS, PHP sessions and an approved database and mailbox. The existing static site still works when the publication API is unavailable; submission pages return 503 until the database is configured.

## Database provisioning

1. In cPanel **MySQL Databases**, create a dedicated NIWFF staging database and a dedicated database user. Assign only the privileges needed for this database (SELECT, INSERT, UPDATE and schema creation for initial import). Do not reuse the HWFC database or account.
2. Import `scripts/schema.sql` via phpMyAdmin or the MySQL CLI. Example (replace the database/user names with the actual cPanel-prefixed names):
   ```bash
   cd ~/public_html/stagingniwf.hillsboroughwalkingfootball.com
   git pull --ff-only
   mysql -u YOUR_DB_USER -p YOUR_DB_NAME < scripts/schema.sql
   ```
3. Create the private configuration outside public_html:
   ```bash
   mkdir -p ~/niwff-private
   chmod 700 ~/niwff-private
   cp scripts/config.example.php ~/niwff-private/config.php
   chmod 600 ~/niwff-private/config.php
   nano ~/niwff-private/config.php
   ```
   Set the actual DSN, user and password. Leave admin_email/from_email empty until verified email addresses and outbound delivery are available. Never place the config or database dump under the web root or commit credentials.
4. Create a named administrator account per website admin:
   ```bash
   php scripts/createAdmin.php YOUR_USERNAME
   ```
   Use a strong unique password; it is read interactively and stored as a hash. No default administrator password is provided. Verify the cPanel PHP CLI uses PHP 8.1 or later.
5. Go to `https://stagingniwf.hillsboroughwalkingfootball.com/admin/`. Sign in and use the review queue.

## Publication behaviour

- Public forms: `/submit.php?kind=event` and `/submit.php?kind=club`.
- All submissions start pending. Submitter name/email are private. An optional club public email is explicitly separate.
- Admin dashboard supports edits and decisions (pending, approved, rejected, changes requested, withdrawn), records the administrator and decision history, and allows withdrawal of published records.
- The public endpoint `/api/public.php` emits only approved records and allow-listed public fields, not private contact details, review notes or administrator information.
- Approved events are shown in the calendar and automatically move to Past Events when their end date has passed in UK time. Approved clubs are added to the existing directory; a legacy listing with the same name is suppressed while the approved record exists.
- Admin notifications are optional and disabled until email settings are filled in; the dashboard queue is always the authoritative source. Requests for changes are recorded but not automatically emailed to submitters.
- Initial implementation stores URLs for posters instead of uploads. Automatic Facebook Event creation, formal affiliation, club accounts/self-service edits, duplicate reconciliation and secure media uploads are separate increments.

## Acceptance test on staging before inviting clubs

Use fictitious test information and an address controlled by the tester.

1. Confirm PHP responds, HTTPS works, and the admin account can sign in. Check `/api/public.php` returns JSON and initially contains no approved records.
2. Submit a test event with a future date and a test club. Confirm receipts and database pending records, and neither appears in the public API or site.
3. Sign in as an admin; edit a field and approve each. Verify public calendar and club detail view update after reloading. Confirm the API contains neither private email/name nor review notes. Confirm date filtering and older archive behaviour.
4. Withdraw/reject the test records; verify they disappear from public data. Inspect the audit log.
5. Try missing/invalid fields, end date earlier than start, invalid URL schemes, a direct unauthenticated admin review, and an invalid CSRF token. Verify they are refused.
6. Check administrator logout and expiry, password hashing, SQL privileges, file permissions, Apache protection for scripts and data, and browser/mobile accessibility. Add server-level rate limiting for both admin sign-in and public submissions before exposing them broadly; the initial session throttle and honeypot are not a robust distributed abuse defence.
7. Test a database backup and restore, configure retention and privacy notices, define admin access revocation and amend/resubmit handling, and confirm notification email delivery if enabled.

Do not use real club representative personal information on staging until the federation approves privacy, retention, security and access arrangements. The site remains noindex, which is not an access control. Protect staging with cPanel authentication if appropriate. No existing NIWFF DNS changes are needed.
