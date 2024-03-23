# Local Development

## Koel

Koel is built with Laravel and Vue.js, and as such, it requires a PHP environment to run.
There are multiple ways to set up a PHP development environment, but if you're on macOS,
the easiest way is probably to use [Laravel Herd](https://herd.laravel.com/).
You will also need [Node.js](https://nodejs.org/) and [Yarn](https://yarnpkg.com/) to build the client application.
For more requirements, refer to the [Requirements section](./guide/getting-started#requirements).

### Running the Local Webserver

First, clone the repository and install the dependencies:

```bash
git clone https://github/com/koel/koel.git
cd koel
composer install
yarn install
```

You can now start the development server with `yarn dev`, which is simply a wrapper around `php artisan serve`:

```bash
yarn dev
  $ php artisan serve

     INFO  Server running on [http://127.0.0.1:8000].

    Press Ctrl+C to stop the server
```

A development version of Koel should now be available at `http://localhost:8000` with full HMR support.
Every change you make to the client application will be reflected in the browser immediately.

### Testing, Linting, Static Analysis, etc.

```bash
# PHP-related code quality tasks
# Basically, take a look at the "scripts" section in composer.json
composer test        # Run the PHP test suite
composer cs          # Run code style checker
composer cs:fix      # Run code style fixer
composer analyze     # Run PHP static analysis

yarn build # Build a production version of the client application

# Client code quality tasks
# These commands need to be run from within the submodule (resources/assets)
yarn test            # Unit testing
yarn lint            # Lint
```

## Koel Docs

Of course, you are welcome to contribute to Koel Docs (this documentation) as well!
Koel’s documentation is built with [VitePress](https://vitepress.dev/) is stored under the `docs` directory in the same repository as Koel.
To start the VitePress instance, use the following command:

```bash
yarn docs:dev
  vitepress v1.0.0

  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
  ➜  press h to show help
```

The documentation should now be available at `http://localhost:5173`.

