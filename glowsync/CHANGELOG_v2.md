# GlowSync v2 — what's new

## 1. Setup
If you already have a `glowsync` database imported, run `database/upgrade_v2.sql`
once to add the new tables/columns. Fresh install? Just import
`database/glowsync.sql` — it already includes everything below.

## 2. New pages
- **inventory.php** — Stock In / Stock Out (with a shared modal), full Stock
  History log (filterable by product/movement type), Low Stock Alerts panel,
  and an Inventory Value stat card (Σ price × stock).
- **users.php** (Admin only) — View/Add/Edit/Delete users, Change Password,
  and Assign Roles (Admin/Staff). Blocks removing your own Admin role,
  deleting yourself, or deleting the last remaining Admin.
- **feedback.php** — Customer Feedback: Name, 1–5 star Rating, Comment, Date.
  Includes an average-rating summary and a rating filter.
- **invoice.php** — Printable Sales Invoice per order (invoice number, line
  item, totals). "Print Receipt" / "Download PDF" both open the browser
  print dialog — choose "Save as PDF" as the destination to download.

## 3. Enhanced existing pages
- **dashboard.php** — Added 4 charts (Chart.js): Monthly Sales, Product
  Sales, Ticket Status, Customer Growth.
- **customer_profile.php** — Added a Support History panel (this customer's
  tickets), alongside the existing Purchase History / Total Orders / Total
  Spending.
- **products.php** — Search by name, filter by category, a Low Stock
  Threshold field, and a live Status badge (In Stock / Low Stock / Out of
  Stock).
- **sales.php** — Filter by customer and date, a 🧾 link to the printable
  invoice per order, and new orders now automatically deduct stock and log
  a Stock Out movement in Inventory.
- **support.php** — Filter by Status and Priority.
- **includes/sidebar.php / topbar.php** — Added Inventory, Feedback, and
  (Admin-only) Users nav links; topbar now shows a role pill.
- **login.php** — Now stores the signed-in user's role in the session so
  role-based nav/access works.

## 4. Notes
- Roles are `Admin` and `Staff`. New sign-ups via `signup.php` remain
  `Staff` by default — promote them to Admin from `users.php`.
- Inventory movements created from Stock In/Out and from new Sales orders
  both write to `inventory_log`, so the Stock History table is a full audit
  trail.
