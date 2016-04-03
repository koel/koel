import { filter } from 'lodash';

/**
 * A Koel-specific song filter, since Vue's filterBy is meant to be generic and thus
 * may be slow for a huge library.
 * We also introduce some custom rules--whitespace trimming and skip empty queries.
 */
export function filterSongBy (songs, search, delimiter) {
    if (!search || !search.trim()) {
        return songs;
    }

    search = ('' + search).toLowerCase().trim();

    return filter(songs, song => {
        return song.title.toLowerCase().indexOf(search) !== -1 ||
            song.album.name.toLowerCase().indexOf(search) !== -1 ||
            song.album.artist.name.toLowerCase().indexOf(search) !== -1;
    });
}
