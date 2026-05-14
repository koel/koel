<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.20
- laravel/framework (LARAVEL) - v12
- laravel/nightwatch (NIGHTWATCH) - v1
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/scout (SCOUT) - v10
- laravel/socialite (SOCIALITE) - v5
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- phpunit/phpunit (PHPUNIT) - v11
- vue (VUE) - v3
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- **Always infer from the existing codebase before writing — and ask when no precedent exists.** Before any new identifier, mock/test pattern, store/service call shape, form wiring, helper choice, spelling, or file layout, grep `app/` and `resources/assets/js/` for how it has been done before, and copy the existing shape exactly. If no precedent exists in the repo, **stop and ask the user** instead of defaulting to whatever you'd write from training data or personal habit. The codebase is the source of truth for *how things are done*, not just *what exists*. "I should have looked" is the symptom; not looking first is the root cause.
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `pnpm run build`, `pnpm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `pnpm run build` or ask the user to run `pnpm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== phpunit/core rules ===

## PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; verify you're using only classes supported by this version. Remember v4 utility renames (e.g. `rounded-sm` is the old `rounded`, `shadow-sm` is the old `shadow`, `outline-hidden` replaces `outline-none`, `ring` is now 1px not 3px).
- The important modifier goes after the class, not before: `text-red-500!`, never `!text-red-500`.
- Opacity modifiers replace the deprecated `*-opacity-*` utilities: `bg-black/50` not `bg-black bg-opacity-50`.
- `@apply` inside Vue scoped `<style>` blocks **must** be preceded by `@reference '@css/app.pcss';` so v4 can resolve theme tokens — the `@css` alias is rewritten to an absolute path by the small custom postcss plugin in `postcss.config.cjs`. Never write relative paths to `app.pcss` in `@reference` directives.
- Theme tokens (custom colors etc.) live in `tailwind.config.js`'s `theme.extend.colors`, linked from `resources/assets/css/app.pcss` via `@config`. Prefer CSS variables on the koel side (defined in `partials/vars.pcss`) over hardcoded colors.
</laravel-boost-guidelines>

## Architecture
- Koel loads data progressively — there is no method to fetch all songs at once. Songs are loaded lazily per screen/context. This is by design for large libraries. Never assume the playable store vault contains all songs.

## Code Organization
- Traits must be placed in a `Concerns` subfolder (namespace) relative to their consumers (e.g. `App\Ai\Tools\Concerns\PlaysMusic`).
- Interfaces must be placed in a `Contracts` subfolder (namespace) relative to their consumers (e.g. `App\Ai\Tools\Contracts\SomeInterface`).

## Spelling
- Use US English spelling for all identifiers (PHP method/class/property names, TS/Vue variables and components), comments, docstrings, doc pages, and user-visible strings: `serialize` / `serializer` (not `serialise`), `color` (not `colour`), `initialize` (not `initialise`), `behavior` (not `behaviour`), `organize` / `organization`, `favorite`, `analyze`. Koel's codebase — and PHP's SPL (`JsonSerializable`) — is uniformly American; don't drift British by reflex.

## Self-Explanatory Code
- Code should read on its own. If a piece of code needs a comment to be understood, that's a signal the code is wrong, not that the comment is needed — refactor it: extract a named helper, rename a variable to encode intent, lift a condition into a named flag, pull a block into a small function. Use a comment only when refactoring genuinely can't carry the intent (a hidden invariant, a workaround tied to a specific external bug, behaviour a reader would otherwise misjudge). Never write comments that narrate the next line, summarise the surrounding block, or restate what well-named identifiers already say.
- Don't use single-letter variable names. The only allowed ones are `i` / `j` for loop counters and `h` for the test harness. For everything else (callback params, destructured fields, lambda args, etc.) pick a name that says what it is.
- Never combine assignment with return. Always `$x = expr;` then `return $x;` on a separate line — `return $x = expr;` cramming two effects into one statement is forbidden in PHP, TS, and JS.

## PHP Conventions
- Always prefer Laravel's built-in helpers over custom implementations (e.g. `str()->plural()`, `Str::slug()`, `Arr::flatten()`, etc.). Do not reimplement what Laravel already provides.
- All methods must have explicit visibility (`public`, `protected`, or `private`). Never omit the visibility keyword, even on interface methods or static methods.
- Methods that don't reference `$this` must be declared `static`, unless the class is injectable (DI service) — in that case, prefer instance methods for better testability and decoupling.
- Always use the least visibility possible. Use `private` by default; only use `protected` or `public` when required by inheritance or external access.
- Never use `empty()` to check arrays. If the variable is known to be an array, use `!$array` instead. Don't compare to `[]` either.
- When a string contains quotes, don't use escaped double quotes (e.g. `"Playlist \"$name\" created"`). Use `sprintf()` with a single-quoted format string instead (e.g. `sprintf('Playlist "%s" created', $name)`).
- Never query models directly (e.g. `Model::query()->where(...)`) outside of the corresponding Repository class. All model lookups and queries must go through the appropriate Repository (e.g. `PlaylistRepository`, `SongRepository`).
- Repositories are read-only — they must never create, update, or delete records. Write operations belong in services or on the models directly.
- For config values needed by services, use the `#[Config('key')]` attribute on constructor parameters (from `Illuminate\Container\Attributes\Config`) — never call `config()` inside the service.
- All closure parameters must be type-hinted. Never use untyped closure arguments (e.g. `function (Builder $query)`, not `function ($query)`).
- When parsing or manipulating URLs, use `Illuminate\Support\Uri` instead of `parse_url()`.
- Do not add return type declarations to controller methods — controller responses are too dynamic/flexible for strict return types.

## Environment Variables Documentation
- When adding, removing, or modifying environment variables in `.env.example`, always update `docs/environment-variables.md` to stay in sync.

## Documentation Pages
- Every doc page under `docs/` must have a `description` in its YAML frontmatter. When creating or editing a doc page, ensure the description accurately summarizes the page content.
- The docs use `vitepress-plugin-llms` to generate `llms.txt` and `llms-full.txt` on build; descriptions are surfaced there.
- Run `bash docs/.vitepress/check-frontmatter.sh` to verify all pages have descriptions.

## Git Commits
- Use [Conventional Commits](https://www.conventionalcommits.org/) for all commit messages (e.g. `fix:`, `feat:`, `chore:`, `test:`, `refactor:`, `docs:`, `ci:`, etc.).
- Focus on the feature/purpose, not implementation details. For example, prefer "feat: show current playing song during radio stream" over "feat: radio station ICY metadata now-playing". Same applies to PR titles.
- Never attribute work to AI in any artifact: no "Generated with Claude Code", "Assisted by AI", "Co-Authored-By: Claude/ChatGPT/Copilot/AI" lines, no AI-tool mentions in commits, PR titles, PR descriptions, issue comments, code comments, or doc pages. The author is the human running the tool.
- When the implementation of a PR changes (e.g. during code review), always update the PR title and description to reflect the current state of the changes.

## Releasing
- To release a new version, run `php artisan koel:release` (interactive) or `php artisan koel:release {patch|minor|major|vX.Y.Z}`. The command handles the version bump, commit, tag, `latest` tag move, and `release` branch sync.
- Do not bump `.version`, create release tags, or move the `latest` tag manually — always use `php artisan koel:release`.
- After the command finishes, the draft release is **not** immediately available on https://github.com/koel/koel/releases. The tag push triggers the `Upload Release Assets` GitHub Action (`.github/workflows/release.yml`), which sets up PHP/Node, builds assets, packages the zip/tarball, and only then creates the draft release. This typically takes several minutes.
- Wait for the workflow to finish before opening the releases page. Poll with `gh run list --workflow=release.yml --limit 1` or block on it with `gh run watch` (pick the most recent run). Once it's `completed/success`, the draft release exists and can be edited/published on GitHub.
- For minor/patch releases, you may be asked to write the release notes. Follow the convention of prior releases (e.g. v9.1.1, v9.1.0, v8.3.1):
    - Title: `vX.Y.Z` (no codename — codenames are reserved for major versions like "Beethoven" in v9.0.0, "Tchaikovsky" in v8.0.0).
    - Body matches GitHub's auto-generated format. Easiest way: `gh api repos/koel/koel/releases/generate-notes -F tag_name=vX.Y.Z -F previous_tag_name=vPREV --jq .body` to fetch the auto-generated body, then apply it with `gh release edit vX.Y.Z --repo koel/koel --notes-file -`.
    - Required structure: a `## What's Changed` section with bullets in the form `* <full conventional-commit subject> by @<author> in <PR or commit URL>`, optionally a `## New Contributors` section, and a trailing `**Full Changelog**: https://github.com/koel/koel/compare/vPREV...vX.Y.Z` line.
    - Do not rewrite or summarize commit subjects — keep them verbatim. Direct-to-master commits without PRs link to the commit SHA URL instead of a PR URL.
    - Leave the release as a draft after editing notes; do not publish unless explicitly told to.

## AI Assistant Tools
- When AI assistant tool capabilities change (added, removed, or updated), always update the sample prompts in `AiSamplePrompts.vue` to reflect the current abilities.

## Lucide Icons
- When importing icons from `lucide-vue-next`, always use the `Icon` suffix (e.g. `SparklesIcon`, not `Sparkles`; `SearchIcon`, not `Search`).

## TypeScript Conventions
- Always prefer generics over type casting when the API supports it (e.g. `container.querySelector<HTMLElement>('.foo')` instead of `container.querySelector('.foo') as HTMLElement`).
- Do not add explicit return types when they can be inferred by the compiler. Only annotate return types when inference is insufficient or ambiguous.
- When using `setTimeout`, `setInterval`, or `requestAnimationFrame`, always ensure they are cleaned up: on component unmount (`onBeforeUnmount`), on state transitions that invalidate them (e.g. drop cancels a pending expand), and when the operation completes. Treat every timer/rAF as a resource that must be explicitly released.

## Vue Template Conventions
- Always use Vue's same-name shorthand for bindings: `:foo` instead of `:foo="foo"`. This applies to props, components, and any v-bind where the attribute name matches the variable name.

## Vue Forms
- Any Vue surface that takes user input and commits it on submit must use the `useForm` composable from `@/composables/useForm` — including inline composers, popovers, and mini name-prompts that aren't named `*Form.vue`. Don't roll your own `ref<string>('')` + manual submit handling.
- Pair it with the canonical wiring: `<form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">`, inputs use `v-koel-focus` (not manual `onMounted` focus) and `required` (not manual `:disabled`), Save is `<Btn type="submit">`, Cancel is `<Btn type="button" @click.prevent="maybeClose">`, and `maybeClose` does `if (isPristine() || (await showConfirmDialog(...))) emit('cancel')`.
- For purely-local submits (no server call), pass `useOverlay: false` and have `onSubmit` just emit. Use the optional `validator` callback for non-HTML5 rules (e.g. trim/whitespace).
- Read `resources/assets/js/components/playlist/CreatePlaylistFolderForm.vue` before writing a new form — that's the reference shape.

## Vue Component Styling
- Put shared/base Tailwind classes directly on the HTML element via the `class` attribute.
- For variant-specific styles (e.g. modes, states), use custom CSS classes (`.initial`, `.chat`, `.user`, `.error`, etc.) with `@apply` in a scoped `<style>` block.
- Do NOT build class strings in JavaScript arrays or computed properties.

## Testing Assertions
- When asserting two Eloquent models are the same, use `assertTrue($modelA->is($modelB))` instead of comparing IDs.

## Model Factories
- Use `createOne()` to create a single model and `createMany()` to create a collection. Never use `create()` directly, as its return type is ambiguous (single model or collection depending on arguments).

## Frontend Testing
- Prefer semantic queries (`getByRole`, `getByLabelText`, `getByText`) via `screen` from `@testing-library/vue`. Use `data-testid` only as a last resort when no semantic query is available.
- `getBy*` queries already throw if the element is not found, so never wrap them in `expect().toBeTruthy()`. Just call `screen.getByTestId('foo')` directly — the throw is the assertion. Use `expect(screen.queryBy*()).toBeNull()` to assert absence.

## Test Class Namespacing
- Unit test classes must mirror the namespace of the class under test. Replace `App\` with `Tests\Unit\` and add a `Test` suffix (e.g. `App\Ai\Services\FavoriteableEntityResolver` → `Tests\Unit\Ai\Services\FavoriteableEntityResolverTest`).
- The test file path must match the namespace (e.g. `tests/Unit/Ai/Services/FavoriteableEntityResolverTest.php`).

## Code Reviews
- When addressing PR review comments, do NOT blindly follow them. Always use your own knowledge and logic to evaluate whether the feedback makes sense. If it doesn't, push back and explain why.

## Linting & Static Analysis
- When running lint or static analysis (backend or frontend), fix ALL warnings and errors to ensure 100% clean output — even pre-existing issues unrelated to current changes.
- **Before creating or updating any PR that touches PHP files**, run all backend gates locally and confirm green: `composer cs` (format check), `composer lint` (mago lint), `composer analyze` (phpstan). Do NOT rely on the pre-commit hook alone — it only catches formatting. Lint and static-analysis failures must be caught locally, not by CI, so the PR isn't created/updated red.

## Vite+ Toolchain

This project uses **Vite+**, a unified toolchain wrapping Vite, Vitest, Oxlint, Oxfmt, and more via a single global CLI called `vp`. Run `vp help` for available commands.

### Key Commands
- `vp dev` — development server
- `vp build` — production build
- `vp test` — run frontend tests (Vitest)
- `vp lint` — lint code (Oxlint)
- `vp fmt` — format code (Oxfmt)
- `vp check` — run format + lint + type checks
- `vp install` / `vp add` / `vp remove` — package management (delegates to pnpm)
- `vp run <script>` — run a package.json script (equivalent of `pnpm run <script>`)

### Imports
- Import from `vite-plus` instead of `vite` (e.g. `import { defineConfig } from 'vite-plus'`)
- Import from `vite-plus/test` instead of `vitest` (e.g. `import { describe, expect, it, vi } from 'vite-plus/test'`)
- Do NOT install `vitest`, `oxlint`, or `oxfmt` directly — Vite+ wraps these tools

### Common Pitfalls
- Do not use `vp vitest` or `vp oxlint` — use `vp test` and `vp lint` instead
- `vp test` runs the built-in test command; `vp run test` runs the `test` script from package.json
- Use `vp check` for validation loops (combines fmt + lint + typecheck)
- Prefer `vp check resources/assets` to scope checks to frontend code
