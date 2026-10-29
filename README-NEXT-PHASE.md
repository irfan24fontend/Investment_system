# Bottle Invest — Next Phase Overlay (Blade)

This is an **overlay for an existing Laravel 11/12 project**, not a complete Laravel installation. It adds a first-pass Blade login/register flow, role middleware, Super Admin offer creation/listing/publish/pause, and Investor dashboard/offer browsing/invest actions on top of the core overlay.

## Important limitations

- This phase does **not** implement withdrawal, settlement, order management, reports, or a live bKash integration yet.
- The bKash adapter must be implemented against approved merchant credentials and official provider verification; never credit a wallet from a browser callback alone.
- Public fundraising and wallet custody must not go live until the business model and payment flows have been reviewed for Bangladesh legal/regulatory requirements.
- Tailwind is loaded from CDN for quick UI prototyping. For production, install/build Tailwind locally and remove the CDN dependency.
- The `routes/web.php` file in this overlay is a route example. Merge these routes into the existing project's `routes/web.php`; do not blindly overwrite routes already in use.

## Install into an existing Laravel project

1. Back up your current project.
2. Copy the `app/`, `database/`, and `resources/` overlay files into the Laravel project, preserving existing files and merging where necessary.
3. Merge the routes from `routes/web.php` into your existing `routes/web.php`.
4. Register the role middleware alias.

### Laravel 11/12 middleware alias

In `bootstrap/app.php`, add `use App\Http\Middleware\EnsureRole;` and register the alias in the existing `withMiddleware` callback:

```php
$middleware->alias([
    'role' => EnsureRole::class,
]);
```

Do not create a second `withMiddleware` callback if one already exists; merge the alias into it.

5. Confirm the existing `users` migration and `User` model are compatible. The new migration adds `role` (default `investor`) and `investor_verified_at` if absent.
6. Ensure the existing core migration has run and PHP extensions `bcmath` and the database driver are enabled.
7. Set `APP_TIMEZONE` only if your app reads it; Laravel's `config/app.php` should use `Asia/Dhaka`.
8. Run:

```bash
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan route:list
```

## Create a Super Admin securely

Do not add public admin registration. Create an admin using a one-time trusted seeder or Tinker command in a protected environment. Example for Tinker (adapt to your User model):

```php
$user = App\Models\User::where('email', 'admin@example.com')->firstOrFail();
$user->forceFill(['role' => 'super_admin'])->save();
```

Change the example email to the intended existing account. Do not expose Tinker publicly or leave a default admin password in source control.

## Known integration tasks before production

- Ensure `User` has standard Laravel `HasFactory`, `Notifiable`, and authentication setup.
- Confirm the login redirect handles all roles in your app.
- Add policies/permission checks for Finance Manager and Order Manager before adding those roles.
- Add CSRF, rate limits, verified email, password reset, and stronger MFA for administrators as appropriate.
- Add tests for offer publication, role access, and the existing single-investor race condition.
- Review `InvestmentService` and `LedgerService` against the production database engine. The existing single-investor test should be run against the same database engine used in production.
- The current public register form creates investor accounts only. It intentionally does not allow choosing an admin role.
