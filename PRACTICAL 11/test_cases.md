# PRACTICAL 11 — Test Cases (manual; fill Actual during demo)

| # | Test | Steps | Expected | Actual |
|---|------|-------|----------|--------|
| 1 | Add valid | Open `add.php` → enter Name `Test User`, Email `test.user@example.com`, Phone `9000000031`, ID `STU021`, Course `Computer Engineering`, Year `2025`, Gender `Male` → Save | Redirect to `index.php` with green “Student added successfully.”; new row appears |  |
| 2 | Add duplicate email | Open `add.php` → reuse Email `aarav.sharma@example.com` with new ID `STU022` → Save | Stays on `add.php` with red “Duplicate email or student ID already exists.”; no insert |  |
| 3 | Edit | On `index.php` click Edit on STU001 → change Phone → Update | Redirect with green “Student updated successfully.”; changed phone in list |  |
| 4 | Soft delete + confirm | On `index.php` click Delete → JS `confirm()` appears → OK → confirm page shows student name → “Yes, Delete” (POST) | Redirect with green soft-delete message; row becomes `inactive` (hidden on default filter, visible under inactive/all) |  |
| 5 | Search | On `index.php` search `q=Aarav` (also try email fragment and course e.g. `Computer`) | Only matching rows listed; count updates |  |
| 6 | Filter | Set course `Information Technology` and status `active` → Search | Only IT + active rows listed |  |
| 7 | Pagination page 2 | With default filter click page `2` | Next 5 records shown; filter params preserved in URL |  |
| 8 | CSV export | Click Export CSV (optionally with a filter active) | `students.csv` downloads with header row + matching records |  |
