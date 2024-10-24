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

You can now start the development server with `composer dev`:

```bash
$ composer dev

> Composer\Config::disableProcessTimeout
> npx concurrently -k -c "#93c5fd,#c4b5fd,#fdba74" "php artisan serve" "php artisan queue:listen --tries=1" "vite" --names=server,queue,vite --restart-tries=3
[vite]
[vite]   VITE v5.1.8  ready in 294 ms
[vite]
[vite]   ➜  Local:   http://localhost:5173/
[vite]   ➜  Network: use --host to expose
[queue]
[queue]    INFO  Processing jobs from the [default] queue.
[queue]
[vite]
[vite]   LARAVEL v10.48.8  plugin v1.0.2
[vite]
[vite]   ➜  APP_URL: http://localhost:8000
[server]
[server]    INFO  Server running on [http://127.0.0.1:8000].
[server]
[server]   Press Ctrl+C to stop the server
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
Koel’s documentation is built with [VitePress](https://vitepress.dev/) and stored under the `docs` directory in the same repository as Koel.
To start the VitePress instance, use the following command:

```bash
yarn docs:dev
  vitepress v1.0.0

  ➜  Local:   http://localhost:5173/
  ➜  Network: use --host to expose
  ➜  press h to show help
```

The documentation should now be available at `http://localhost:5173`.
For more information on how to work with VitePress, check out its [official website](https://vitepress.dev/).

