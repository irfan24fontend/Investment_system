# What was fixed (Oct 29)
1. **Offer never became `funded`** (InvestmentService used update() on a guarded column). Result: admin never saw
   "Progress / Complete", order manager could not move funded orders. Fixed with forceFill + regression test.
2. **Double-spend race on MySQL**: balance was read from an old REPEATABLE READ snapshot after taking the lock. Balance
   checks before spending (invest, withdraw) now use a locking current read: LedgerAccount::balance(true).
3. **Timezone** was hard-coded UTC in config/app.php; now Asia/Dhaka (APP_TIMEZONE). NOTE: offers saved before this
   change keep old literal times; re-check their deadlines.
4. Platform ledger accounts (user_id NULL) could be duplicated under concurrency -> created once by a new migration.
5. Route hack `role:super_admin_only` -> `role:super_admin`.
6. Added audit log for investment.confirmed.
7. Removed dead/duplicate files: Admin/OfferController, EnsureRole, admin/offers/*, investor/offers/* (all pointed to
   non-existent routes), FakeBkashGateway (simulated payments, route did not exist), old overlay zips/readmes, stray jpg/csv,
   sqlite file, redundant users migration, laravel.log.
8. .env.example now matches the real config (MySQL, timezone, bank/bKash keys). Your own .env is NOT in this zip.
   Your .env had leading spaces on the DB_* lines; remove them.
