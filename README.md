# Inaya Supplies - Order-based investment platform (Laravel 11/12, MySQL)

## Setup (existing project: just overwrite files, keep your own .env and vendor/)
    cp .env.example .env        # first time only; set DB_* and SEED_STAFF_PASSWORD
    composer install
    php artisan key:generate    # first time only
    php artisan optimize:clear
    php artisan migrate --seed
    php artisan test

Staff logins after seeding (password = SEED_STAFF_PASSWORD): super_admin@example.test, finance_manager@example.test,
finance_manager_2@example.test, order_manager@example.test. CHANGE THESE EMAILS before real use.

Cron (cPanel, every minute): `* * * * * cd /home/USER/app && php artisan schedule:run >> /dev/null 2>&1`

## The one workflow (who does what)
1. Investor registers -> staff (finance/super admin) VERIFY the investor.
2. Investor deposits (Islami Bank slip or bKash TrxID) -> finance APPROVES -> wallet credited (ledger).
3. Super admin creates offer (draft) -> PUBLISH. Offer shows to verified investors until deadline.
4. Investor presses Invest -> one DB transaction: row lock, deadline check, wallet check, UNIQUE(active_offer_id),
   ledger debit. Offer status becomes `funded` and disappears for everyone else.
5. Super admin: Offers -> "Progress / Complete" -> post progress updates (investor is notified).
6. Settlement, either way:
   a) Super admin "Complete offer": enters real revenue/refunds/actual cost -> auto prepare+approve+credit.
   b) Order manager sets order to `financial_verification` -> Finance prepares -> a DIFFERENT finance user approves.
7. Investor wallet is credited principal + profit share, or principal - loss. Investor may withdraw (reserved until staff pays).

Roles: super_admin (everything) | finance_manager (investors, deposits, withdrawals, settlements, reports)
| order_manager (orders only, no money) | investor.

## Not built yet / decisions needed
Live bKash API (needs merchant credentials; manual TrxID approval is used), PDF agreements, email/SMS, Bangla UI toggle,
support tickets, 2FA, document uploads for offers, DB-level append-only triggers on ledger tables.
LEGAL: public fundraising with returns in Bangladesh needs legal/regulatory review before real public use.
Production: APP_DEBUG=false, HTTPS, daily mysqldump, never commit .env.
