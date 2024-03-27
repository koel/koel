# Instant Search

Powered by [Laravel Scout](https://github.com/laravel/scout), Koel provides an instant search feature that performs full-text,
fuzzy searches against your music collection and returns the most relevant results as you type.

<video controls playsinline poster="../assets/videos/search-poster.webp">
  <source src="../assets/videos/search.mp4" type="video/mp4"/>
  <source src="../assets/videos/search.webm" type="video/webm"/>
</video>

## Configuration

Out of the box, Koel uses [TNTSearch](https://github.com/teamtnt/tntsearch), a powerful full-text search engine written entirely in PHP and requires no configuration.

Switching to [Algolia](https://www.algolia.com/) is just a matter of changing the driver value to `algolia` and populating the credentials into `.env`:

```
SCOUT_DRIVER=algolia
ALGOLIA_APP_ID=<your-algolia-app-id>
ALGOLIA_SECRET=<your-algolia-secret>
```

Similarly, you can use [Meilisearch](https://www.meilisearch.com/):

```
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=<your-meilisearch-host>
MEILISEARCH_KEY=<your-meilisearch-key>
```

## Update the Search Index

If the configuration is done after you have already populated Koel with songs, you'll need to update the search index.
You can do so by running this command:

```bash
php artisan koel:search:import
```

Afterward and for future changes, the index will be updated automatically whenever you add, update, or delete songs, albums, or artists.
There's literally nothing you need to do to keep the index up to date.

## Usage

To use the instant search, simply start typing in the search box at the top left of the screen or pressing <kbd>F</kbd>.
You'll see the results appear as you type.
