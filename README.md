# koel [![e2e](https://github.com/koel/koel/workflows/e2e/badge.svg)](https://github.com/koel/koel/actions?query=workflow%3Ae2e) [![unit](https://github.com/koel/koel/workflows/unit/badge.svg)](https://github.com/koel/koel/actions?query=workflow%3Aunit) ![Code Quality](https://scrutinizer-ci.com/g/phanan/koel/badges/quality-score.png?b=master) [![codecov](https://codecov.io/gh/koel/koel/branch/master/graph/badge.svg)](https://codecov.io/gh/koel/koel) [![OpenCollective](https://opencollective.com/koel/backers/badge.svg)](#backers) [![OpenCollective](https://opencollective.com/koel/sponsors/badge.svg)](#sponsors)

![Showcase](https://koel.dev/img/showcase.png)

## Intro

**Koel** (also stylized as **koel**, with a lowercase k) is a simple web-based personal audio streaming service written in [Vue](http://vuejs.org/) on the client side and [Laravel](http://laravel.com/) on the server side. Targeting web developers, Koel embraces some of the more modern web technologies – CSS grid, audio, and drag-and-drop API to name a few – to do its job.

## Install and Upgrade Guide

For system requirements, installation/upgrade guides, troubleshooting etc., head over to the [Official Documentation](https://docs.koel.dev).

## API Docs

If you're interested in the development of a client, Koel's official API documentation is available [here](https://api-docs.koel.dev).

## Development

Since Koel makes use of [git submodules](https://git-scm.com/book/en/v2/Git-Tools-Submodules), you'll want to make sure the submodule is up-to-date:

```bash
git pull
git submodule update --init --recursive --remote

# install the submodule dependencies
cd resources/assets
yarn install
```

To start the **PHP dev server**, which serves as the API of the application, run the following command from the root directory. By default, the server will listen at port `8000`.

```bash
php artisan serve
```

For the **client application** itself, run this command:

```bash
yarn hot
```

A development version of Koel should now be available at `http://localhost:8080` with full support for hot module reloading.

Alternatively, you can start both the PHP server and the client application in one go with `yarn dev`, which uses [`start-server-and-test`](https://github.com/bahmutov/start-server-and-test) under the hood.

## Testing, Linting, Static Analysis and Stuff

```bash
# PHP-related code quality tasks
# Basically, take a look at the "scripts" section in composer.json
composer test        # Run the PHP test suite
composer cs          # Run code style checker
composer cs:fix      # Run code style fixer 
composer analyze     # Run PHP static analysis

yarn build # Build a production version of the client application

# Client/E2E code quality tasks
# You may want to run `yarn build` first.
yarn test:e2e        # Run the Cypress test suite interactively
yarn test:e2e:ci     # Run the Cypress test suite non-interactively (CI mode)
# These commands need to be run from within the submodule (resources/assets)
yarn lint            # Lint
yarn type-check      # TypeScript type checking
yarn test            # Unit testing
```

> Note: If you're already running `yarn test:e2e`, there's no need to start a dev server. `yarn test:e2e` calls `yarn dev` internally and will eliminate the existing `yarn dev` process, if any. 

> A quick and easy way to start hacking on koel is to open and run this repo in Gitpod, an online IDE with full Laravel support.
> 
> [![Open in Gitpod](https://gitpod.io/button/open-in-gitpod.svg)](https://gitpod.io/#https://github.com/koel/koel)

## Backers

[Support me on OpenCollective](https://opencollective.com/koel#backer) with a monthly donation and help me continue building Koel.

<a href="https://opencollective.com/koel/backer/0/website" target="_blank"><img src="https://opencollective.com/koel/backer/0/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/1/website" target="_blank"><img src="https://opencollective.com/koel/backer/1/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/2/website" target="_blank"><img src="https://opencollective.com/koel/backer/2/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/3/website" target="_blank"><img src="https://opencollective.com/koel/backer/3/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/4/website" target="_blank"><img src="https://opencollective.com/koel/backer/4/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/5/website" target="_blank"><img src="https://opencollective.com/koel/backer/5/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/6/website" target="_blank"><img src="https://opencollective.com/koel/backer/6/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/7/website" target="_blank"><img src="https://opencollective.com/koel/backer/7/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/8/website" target="_blank"><img src="https://opencollective.com/koel/backer/8/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/9/website" target="_blank"><img src="https://opencollective.com/koel/backer/9/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/10/website" target="_blank"><img src="https://opencollective.com/koel/backer/10/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/11/website" target="_blank"><img src="https://opencollective.com/koel/backer/11/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/12/website" target="_blank"><img src="https://opencollective.com/koel/backer/12/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/13/website" target="_blank"><img src="https://opencollective.com/koel/backer/13/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/14/website" target="_blank"><img src="https://opencollective.com/koel/backer/14/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/15/website" target="_blank"><img src="https://opencollective.com/koel/backer/15/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/16/website" target="_blank"><img src="https://opencollective.com/koel/backer/16/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/17/website" target="_blank"><img src="https://opencollective.com/koel/backer/17/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/18/website" target="_blank"><img src="https://opencollective.com/koel/backer/18/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/19/website" target="_blank"><img src="https://opencollective.com/koel/backer/19/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/20/website" target="_blank"><img src="https://opencollective.com/koel/backer/20/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/21/website" target="_blank"><img src="https://opencollective.com/koel/backer/21/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/22/website" target="_blank"><img src="https://opencollective.com/koel/backer/22/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/23/website" target="_blank"><img src="https://opencollective.com/koel/backer/23/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/24/website" target="_blank"><img src="https://opencollective.com/koel/backer/24/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/25/website" target="_blank"><img src="https://opencollective.com/koel/backer/25/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/26/website" target="_blank"><img src="https://opencollective.com/koel/backer/26/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/27/website" target="_blank"><img src="https://opencollective.com/koel/backer/27/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/28/website" target="_blank"><img src="https://opencollective.com/koel/backer/28/avatar.svg"></a>
<a href="https://opencollective.com/koel/backer/29/website" target="_blank"><img src="https://opencollective.com/koel/backer/29/avatar.svg"></a>

## Sponsors

#### GitHub Sponsors

* Eduardo San Martin Morote ([@posva](https://github.com/posva))
* [You](https://github.com/users/phanan/sponsorship)?

#### OpenCollective

[Become a sponsor on OpenCollective](https://opencollective.com/koel#sponsor) and get your logo on our README on Github with a link to your site.

<a href="https://opencollective.com/koel/sponsor/0/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/0/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/1/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/1/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/2/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/2/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/3/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/3/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/4/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/4/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/5/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/5/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/6/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/6/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/7/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/7/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/8/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/8/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/9/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/9/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/10/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/10/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/11/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/11/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/12/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/12/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/13/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/13/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/14/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/14/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/15/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/15/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/16/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/16/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/17/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/17/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/18/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/18/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/19/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/19/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/20/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/20/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/21/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/21/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/22/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/22/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/23/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/23/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/24/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/24/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/25/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/25/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/26/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/26/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/27/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/27/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/28/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/28/avatar.svg"></a>
<a href="https://opencollective.com/koel/sponsor/29/website" target="_blank"><img src="https://opencollective.com/koel/sponsor/29/avatar.svg"></a>
