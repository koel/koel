# Local Development

Koel is built with Laravel and Vue.js, and as such, it requires a PHP environment to run. 
There are multiple ways to set up a PHP development environment, but if you're on macOS, 
the easiest way is probably to use [Laravel Herd](https://herd.laravel.com/). 
You will also need [Node.js](https://nodejs.org/) and [Yarn](https://yarnpkg.com/) to build the client application.
For more requirements, refer to the [Requirements section](./guide/getting-started#requirements).

### Running the Local Webserver

Start both the PHP server and the client application in one go with `yarn dev`, which uses [`start-server-and-test`](https://github.com/bahmutov/start-server-and-test) to manage both [vite](https://vitest.dev/) and Laravel:

```bash
yarn dev
  vite v2.9.13 dev server running at:

  > Local: http://localhost:3000/
  > Network: use `--host` to expose

  ready in 761ms.

  Laravel v9.22.1

  > APP_URL: http://localhost:8000
```

A development version of Koel should now be available at `http://localhost:8000` with full HMR support.

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

### Ask for Help

If you're stuck, the [issue page](https://github.com/koel/koel/issues) on GitHub is a good place to ask for help.

