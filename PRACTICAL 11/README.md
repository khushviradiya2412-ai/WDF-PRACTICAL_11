# PRACTICAL 11 — Student Module (Add / View / Edit / Delete, Search / Filter)

## Purpose
Student management for StudentHub (ITUE203 WDF): list students with search
(name/email/course), course + status filters, pagination (5/page), add, edit,
soft-delete via `status` column, CSV export of the current filter, and
success/failure flash messages. All user input uses MySQLi prepared
statements; all output uses `htmlspecialchars`; server-side validation on
every write; delete requires confirmation (JS `confirm()` + confirm page, POST only).

## Setup
1. Start Apache + MySQL in XAMPP.
2. Import `database.sql` via phpMyAdmin (creates DB `studenthub_p11`, tables
   `users`, `students`, `events`, `registrations`, `audit_logs`; seeds 2 users
   + 20 students STU001–STU020).
3. Place folder `PRACTICAL 11` under `htdocs` (or your server docroot).
4. Open `index.php` in browser:
   `http://localhost/PRACTICAL%2011/index.php` (adjust to your path).

## Credentials (seeded, exact)
- Admin: `admin` / `Admin@123`
- Student: `student` / `Student@123`

## Files
- `database.sql` — schema + seeds (STU020 `inactive` to demo soft delete/filter).
- `db.php` — MySQLi connection (`studenthub_p11`, utf8mb4).
- `index.php` — list (active by default), search `q`, course dropdown, status
  filter (active/inactive/all), pagination 5/page, edit/delete links, export link.
- `add.php` / `edit.php` — validated forms + duplicate email/student_id check.
- `delete.php` — GET confirm page with student name; POST soft-deletes
  (`UPDATE status='inactive'`).
- `export_csv.php` — CSV of current filter (`fputcsv`, download headers).
- `assets/style.css` — copied from `PRACTICAL 7/CSS/style.css`.
- `test_cases.md` — manual test table (Actual column blank for student).

## Viva Q&A
1. **Why prepared statements?** They separate SQL code from data: user input is
   sent as bound parameters, so quotes/wildcards cannot break out and inject
   SQL. All `SELECT/INSERT/UPDATE` here use `prepare()` + `bind_param()`.
2. **What is soft delete?** Instead of `DELETE FROM`, we run
   `UPDATE students SET status='inactive' WHERE id=?`. The row is kept for
   history/audit; lists show `active` by default with a filter for
   `inactive`/`all`; STU020 ships as `inactive` to demonstrate it.
3. **How does pagination work?** Count matching rows (`SELECT COUNT(*)`) for
   total pages; then fetch one page with `LIMIT ? OFFSET ?` bound as integers
   (`offset = (page-1) * 5`). Page links preserve `q/course/status` so the
   filter persists across pages.
