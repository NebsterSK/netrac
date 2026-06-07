# Netrac

A personal, single-user dashboard for tracking finances and workouts — built with Laravel, Inertia, and Vue.

- **Finance:** monthly balances and net-worth statements with charts.
- **Health:** exercise categories (priority board), exercises, and workout sessions.

## Stack

- PHP 8.5, Laravel 13
- Inertia.js v3 + Vue 3 + TypeScript, Tailwind CSS v4, ShadCN Vue (reka-ui)
- Laravel Fortify (auth), Laravel Wayfinder (typed routes)
- `spatie/laravel-data` (DTOs) + `spatie/laravel-typescript-transformer` (generated TS types)
- `spatie/laravel-query-builder` (list filtering / sorting / pagination)
- Pest (tests), Larastan, Pint, ESLint, Prettier

## Local development

Served by **Laravel Herd** at `https://netrac.test` (project lives at `E:\webs\netrac`). CLI aliases for `php`, `composer`, and `npm` are defined in `.bash_profile`.

```bash
# install
composer install
npm install

# database (seed includes Finance + Health demo data)
php artisan migrate --seed
composer resetup          # full reinstall + npm install + migrate:fresh --seed

# frontend (Herd serves PHP automatically)
npm run watch             # Vite dev server / HMR
npm run build             # production build

# codegen — run after backend changes
php artisan typescript:transform                 # regenerate resources/js/generated/generated.d.ts
php artisan wayfinder:generate --with-form       # regenerate @/routes + @/actions (keep --with-form)
composer models                                  # refresh ide-helper model docblocks

# quality (run manually)
composer pint
composer larastan
npm run eslint
npm run prettier
```

> Wayfinder note: the Vite plugin generates route helpers with `formVariants: true`. When regenerating from the CLI, always pass `--with-form`, otherwise pages that use `route.form()` (auth/settings) break.

## Architecture

### Modules

Code is namespaced by domain module — **Finance** and **Health** — across every layer:

```
app/Models/{Module}            app/Data/{Module}
app/Http/Controllers/{Module}  app/Http/Requests/{Module}
database/factories/{Module}    database/seeders/{Module}
```

`User` stays at `app/Models/User` (shared). Add new domain code under the matching module.

### DTOs and TypeScript

Controllers return `spatie/laravel-data` DTOs (`app/Data/{Module}`) from `Inertia::render` instead of hand-mapped arrays. Each DTO is annotated `#[TypeScript]` and compiled to ambient `App.Data.{Module}.*` types in `resources/js/generated/generated.d.ts` by `php artisan typescript:transform` (config in `app/Providers/TypeScriptTransformerServiceProvider.php`). Vue pages alias those generated types rather than re-declaring interfaces.

Validation always stays in FormRequests; DTOs are view-models and typed write payloads only (`SomeData::from($request->validated())`).

Caveat: laravel-data's bundled `DataTypeScriptTransformer` targets typescript-transformer v2 and breaks on the installed v3, so the project uses the v3-native `AttributedClassTransformer` plus a Carbon → `string` type replacement.

### Listing pages

Index pages use `spatie/laravel-query-builder` for search, filtering, and sorting with `paginate(20)`. A FormRequest validates the query params; the controller returns DTO rows, a pagination `meta` block, the filter options, and the active `filters`/`sort`. The Vue page mirrors that state to the URL (`router.get` with `preserveState`/`replace`), debounces only the search box (300ms), and applies filters/sort/pagination instantly. Reference implementation: `ExerciseController` + `resources/js/pages/health/Exercise.vue`.

### Migrations

Reference data is seeded in self-contained migrations using `DB::table(...)` with hardcoded values (no enum or model references), so each migration stays an immutable, dependency-free snapshot.

## Conventions

Coding rules for contributors (and the AI assistant) live in `CLAUDE.md`.
