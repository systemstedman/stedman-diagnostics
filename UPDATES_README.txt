STEDMAN DIAGNOSTICS - SPARES WORKFLOW AND FILTERED EXPORT UPDATE

IMPORTANT DATABASE STEP
1. Back up the stedman_diagnostics database first.
2. Open phpMyAdmin -> stedman_diagnostics -> SQL.
3. Run database/add_spares_workflow_columns.sql once.
   It adds service_calls.spares_required and service_calls.spares_replaced.
4. Copy the updated project files over the existing project folder.
5. Sign out and sign back in, then press Ctrl+F5.

Changes:
- Stores the two Yes/No spares answers in separate database columns.
- Reopens active calls with saved visit times, observations, actions, spares answers,
  and spares details populated and editable.
- Keeps a call active when spares are required but not yet replaced.
- Shows both spares answers on service-call-view.php.
- Export buttons now export visible table columns and visible/filtered rows from
  the currently displayed table instead of re-fetching every record.

Files changed:
- api/complete-service.php
- assets/js/app.js
- service-call-view.php
- database/add_spares_workflow_columns.sql

Please test first on a database backup. This package has not been tested against
your live XAMPP database; the SQL migration must be run before the updated API
can save the new columns.
