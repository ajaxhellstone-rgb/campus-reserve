<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Local development

Local development uses the SQLite database at `database/database.sqlite`, file-based sessions and cache, synchronous jobs, and logged email by default. This keeps the local site independent of Wi-Fi and external services. Run `php artisan migrate` after pulling new migrations. The SQLite database is local and is not synchronized with the Render PostgreSQL database.

Set `DB_CONNECTION=pgsql` and the appropriate database credentials in your private `.env` only when you intentionally want local development to use the remote database; that requires an internet connection.

## Render demo deployment

This repository includes a Render Blueprint (`render.yaml`) and Dockerfile. In Render, create a new Blueprint and connect `ajaxhellstone-rgb/campus-reserve` on the `main` branch. The web service must be configured with the intended PostgreSQL connection (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `DB_SSLMODE`) and the same Laravel `APP_KEY` used to encrypt existing user data. These values are intentionally not synchronized from the Blueprint. Database migrations run when the container starts.

The container runs Laravel's scheduler alongside Apache for due property-return reminders, rejection of past pending reservations, and completion of ended approved reservations, including property reservations with borrowed items. Render's free web service can sleep, so scheduled tasks may be delayed until the service wakes; use an always-on service or external scheduler for punctual processing. The filesystem is temporary (including profile picture uploads). Add Google OAuth credentials in the Render environment to enable Google sign-in.

Email can use the Gmail API over HTTPS without owning a domain. In Google Cloud, enable the Gmail API, configure the OAuth consent screen, and create a Web application OAuth client with `https://developers.google.com/oauthplayground` as an authorized redirect URI. In OAuth 2.0 Playground, open its settings, enable “Use your own OAuth credentials,” and enter that client ID and secret. Authorize `https://www.googleapis.com/auth/gmail.send`, exchange the authorization code for tokens, and copy the refresh token. Add the sending account as a test user if the consent screen is in Testing mode. Set `MAIL_MAILER=gmail_api`, `MAIL_FROM_ADDRESS` to that authorized Gmail address, and `GMAIL_MAIL_CLIENT_ID`, `GMAIL_MAIL_CLIENT_SECRET`, and `GMAIL_MAIL_REFRESH_TOKEN` in Render. Keep these OAuth credentials private. Refresh tokens for external testing apps can expire after seven days. Gmail SMTP is not used by this transport. Resend remains available as an alternative when its sender domain is verified.

Users can opt in to facility and property reservation submission, approval, and denial emails in profile settings; it is disabled by default. In local development, the example configuration logs emails unless `MAIL_MAILER` is changed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
