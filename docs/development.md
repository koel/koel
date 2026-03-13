---
description: Setting up a local Koel development environment, running the dev server, testing, linting, and contributing.
---

# Local Development

## Koel

:::tip Code Wiki
It's highly recommended to take a look at Koel's
[Code Wiki](https://codewiki.google/github.com/koel/koel) to have a better understanding of the code.
:::

Koel is built with Laravel and Vue.js, and as such, it requires a PHP environment to run.
There are multiple ways to set up a PHP development environment, but if you're on macOS,
the easiest way is probably to use [Laravel Herd](https://herd.laravel.com/).
You will also need [Node.js](https://nodejs.org/) and [pnpm](https://pnpm.io/) to build the client application.
For more requirements, refer to the [Requirements section](./guide/getting-started#requirements).

### Running the Local Webserver

First, clone the repository and install the dependencies:

```bash
git clone https://github.com/koel/koel.git
cd koel
composer install
pnpm install
```

You can now start the development server with `composer dev`:

```bash
composer dev
```

A development version of Koel should now be available at `http://localhost:8000` with full HMR support.
Every change you make to the client application will be reflected in the browser immediately.

### Testing, Linting, Static Analysis, etc.

```bash
# PHP-related code quality tasks
composer test        # Run the PHP test suite
composer cs          # Run code style checker
composer cs:fix      # Run code style fixer
composer analyze     # Run PHP static analysis

# Client code quality tasks
pnpm run build       # Build a production version of the client application
pnpm run test        # Unit testing
pnpm run check       # Format + lint + type check
```

## Koel Docs

Of course, you are welcome to contribute to Koel Docs (this documentation) as well!
Koel’s documentation is built with [VitePress](https://vitepress.dev/) and stored under the `docs` directory in the same repository as Koel.
To start the VitePress instance, use the following command:

```bash
pnpm docs:dev
  vitepress v1.0.0

  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
  ➜  press h to show help
```

The documentation should now be available at `http://localhost:5173`.
For more information on how to work with VitePress, check out its [official website](https://vitepress.dev/).

