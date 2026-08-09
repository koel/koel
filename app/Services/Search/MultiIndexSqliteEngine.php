<?php

namespace App\Services\Search;

use TeamTNT\TNTSearch\Engines\SqliteEngine;

/**
 * Koel writes to several TNTSearch indexes (songs, albums, artists, playlists, podcasts) from a single
 * process — a library scan saves an artist, an album and a song one after another, and `koel:search:import`
 * walks every model in one run.
 *
 * Upstream's SqliteEngine::selectIndex() swaps the index connection but keeps the wordlist statements it
 * prepared against the previous one, so once a second index is selected every term is written to the
 * index that came before it. The affected records end up with no terms of their own and become impossible
 * to find.
 *
 * Reported at https://github.com/teamtnt/tntsearch/issues/367; this class can go once that ships.
 */
class MultiIndexSqliteEngine extends SqliteEngine
{
    public function selectIndex($indexName): void
    {
        parent::selectIndex($indexName);

        $this->statementsPrepared = false;
        $this->inMemoryTerms = [];
    }
}
