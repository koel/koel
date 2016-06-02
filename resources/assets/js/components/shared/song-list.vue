<template>
    <div class="song-list-wrap main-scroll-wrap {{ type }}"
        v-el:wrapper
        tabindex="1"
        @scroll="scrolling"
        @keydown.delete.prevent.stop="handleDelete"
        @keydown.enter.prevent.stop="handleEnter"
        @keydown.a.prevent="handleA"
    >
        <table v-show="items.length">
            <thead>
                <tr>
                    <th @click="sort('track')" class="track-number">#
                        <i class="fa fa-angle-down" v-show="sortKey === 'track' && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortKey === 'track' && order < 0"></i>
                    </th>
                    <th @click="sort('title')">Title
                        <i class="fa fa-angle-down" v-show="sortKey === 'title' && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortKey === 'title' && order < 0"></i>
                    </th>
                    <th @click="sort(['album.artist.name', 'album.name', 'track'])">Artist
                        <i class="fa fa-angle-down" v-show="sortingByArtist && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortingByArtist && order < 0"></i>
                    </th>
                    <th @click="sort(['album.name', 'track'])">Album
                        <i class="fa fa-angle-down" v-show="sortingByAlbum && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortingByAlbum && order < 0"></i>
                    </th>
                    <th @click="sort('fmtLength')" class="time">Time
                        <i class="fa fa-angle-down" v-show="sortKey === 'fmtLength' && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortKey === 'fmtLength' && order < 0"></i>
                    </th>
                    <th class="play"></th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="item in items
                        | caseInsensitiveOrderBy sortKey order
                        | filterSongBy q
                        | limitBy numOfItems"
                    is="song-item"
                    data-track="{{ item.track }}"
                    data-song-id="{{ item.id }}"
                    track-by="id"
                    :song="item"
                    v-ref:rows
                    @click="rowClick(item.id, $event)"
                    draggable="true"
                    @dragstart="dragStart(item.id, $event)"
                    @dragleave="removeDroppableState"
                    @dragover.prevent="allowDrop(item.id, $event)"
                    @drop.stop.prevent="handleDrop(item.id, $event)"
                    @contextmenu.prevent="openContextMenu(item.id, $event)"
                >
                </tr>
            </tbody>
        </table>

        <song-menu v-ref:context-menu :songs="selectedSongs"></song-menu>
        <to-top-button :showing="showBackToTop"></to-top-button>
    </div>
</template>

<script>
    import { find, invokeMap, filter, map } from 'lodash';
    import isMobile from 'ismobilejs';
    import $ from 'jquery';

    import songItem from './song-item.vue';
    import songMenu from './song-menu.vue';
    import infiniteScroll from '../../mixins/infinite-scroll';
    import playlistStore from '../../stores/playlist';
    import queueStore from '../../stores/queue';
    import songStore from '../../stores/song';
    import favoriteStore from '../../stores/favorite';
    import playback from '../../services/playback';

    export default {
        props: ['items', 'type', 'playlist', 'selectedSongs', 'sortable'],
        mixins: [infiniteScroll],
        components: { songItem, songMenu },

        data() {
            return {
                lastSelectedRow: null,
                q: '', // The filter query
                sortKey: '',
                order: 1,
                componentCache: {},
                sortingByAlbum: false,
                sortingByArtist: false,
            };
        },

        watch: {
            /**
             * Watch the items.
             */
            items() {
                if (this.sortable === false) {
                    this.sortKey = '';
                }

                // Dispatch this event for the parent to update the song count and duration status.
                this.$dispatch('songlist:changed', {
                    songCount: this.items.length,
                    totalLength: songStore.getLength(this.items, true),
                });
            },
        },

        methods: {
            /**
             * Handle sorting the song list.
             *
             * @param  {String} key The sort key. Can be 'title', 'album', 'artist', or 'fmtLength'
             */
            sort(key) {
                if (this.sortable === false) {
                    return;
                }

                this.sortKey = key;
                this.order = 0 - this.order;
                this.sortingByAlbum = Array.isArray(this.sortKey) && this.sortKey[0] === 'album.name';
                this.sortingByArtist = Array.isArray(this.sortKey) && this.sortKey[0] === 'album.artist.name';
            },

            /**
             * Execute the corresponding reaction(s) when the user presses Delete.
             */
            handleDelete() {
                const songs = this.selectedSongs;

                if (!songs.length) {
                    return;
                }

                switch (this.type) {
                    case 'queue':
                        queueStore.unqueue(songs);
                        break;
                    case 'favorites':
                        favoriteStore.unlike(songs);
                        break;
                    case 'playlist':
                        playlistStore.removeSongs(this.playlist, songs);
                        break;
                    default:
                        break;
                }

                this.clearSelection();
            },

            /**
             * Execute the corresponding reaction(s) when the user presses Enter.
             *
             * @param {Object} e The keydown event.
             */
            handleEnter(e) {
                const songs = this.selectedSongs;

                if (!songs.length) {
                    return;
                }

                if (songs.length === 1) {
                    // Just play the song
                    playback.play(songs[0]);

                    return;
                }

                switch (this.type) {
                    case 'queue':
                        // Play the first song selected if we're in Queue screen.
                        playback.play(songs[0]);
                        break;
                    case 'favorites':
                    case 'playlist':
                    default:
                        //
                        // --------------------------------------------------------------------
                        // For other screens, follow this map:
                        //
                        //  • Enter: Queue songs to bottom
                        //  • Shift+Enter: Queues song to top
                        //  • Cmd/Ctrl+Enter: Queues song to bottom and play the first selected song
                        //  • Cmd/Ctrl+Shift+Enter: Queue songs to top and play the first queued song
                        //
                        // Also, if there's only one song selected, play it right away.
                        // --------------------------------------------------------------------
                        //
                        queueStore.queue(songs, false, e.shiftKey);

                        this.$nextTick(() => {
                            this.$root.loadMainView('queue');

                            if (e.ctrlKey || e.metaKey || songs.length === 1) {
                                playback.play(songs[0]);
                            }
                        });

                        break;
                }

                this.clearSelection();
            },

            /**
             * Get the song-item component that's associated with a song ID.
             *
             * @param  {String} id The song ID.
             *
             * @return {Object}    The Vue compoenent
             */
            getComponentBySongId(id) {
                // A Vue component can be removed (as a result of filter for example), so we check for its $el as well.
                if (!this.componentCache[id] || !this.componentCache[id].$el) {
                    this.componentCache[id] = find(this.$refs.rows, { song: { id } });
                }

                return this.componentCache[id];
            },

            /**
             * Capture A keydown event and select all if applicable.
             *
             * @param {Object} e The keydown event.
             */
            handleA(e) {
                if (!e.metaKey && !e.ctrlKey) {
                    return;
                }

                invokeMap(this.$refs.rows, 'select');
                this.gatherSelected();
            },

            /**
             * Gather all selected songs.
             *
             * @return {Array.<Object>} An array of Song objects
             */
            gatherSelected() {
                const selectedRows = filter(this.$refs.rows, { selected: true });
                const ids = map(selectedRows, row => row.song.id);

                this.selectedSongs = songStore.byIds(ids);
            },

            /**
             * -----------------------------------------------------------
             * The next four methods are to deal with selection.
             *
             * Credits: http://stackoverflow.com/a/17966381/794641 by andyb
             * -----------------------------------------------------------
             */

            /**
             * Handle the click event on a row to perform selection.
             *
             * @param  {String} songId
             * @param  {Object} e
             */
            rowClick(songId, e) {
                const row = this.getComponentBySongId(songId);

                // If we're on a touch device, or if Ctrl/Cmd key is pressed, just toggle selection.
                if (isMobile.any) {
                    this.toggleRow(row);
                    this.gatherSelected();

                    return;
                }

                if (e.ctrlKey || e.metaKey) {
                    this.toggleRow(row);
                }

                if (e.button === 0) {
                    if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
                        this.clearSelection();
                        this.toggleRow(row);
                    }

                    if (e.shiftKey && this.lastSelectedRow && this.lastSelectedRow.$el) {
                        this.selectRowsBetweenIndexes([this.lastSelectedRow.$el.rowIndex, row.$el.rowIndex]);
                    }
                }

                this.gatherSelected();
            },

            /**
             * Toggle select/unslect a row.
             *
             * @param  {Object} row The song-item component
             */
            toggleRow(row) {
                row.toggleSelectedState();
                this.lastSelectedRow = row;
            },

            selectRowsBetweenIndexes(indexes) {
                indexes.sort((a, b) => a - b);

                const rows = $(this.$els.wrapper).find('tbody tr');

                for (let i = indexes[0]; i <= indexes[1]; ++i) {
                    this.getComponentBySongId($(rows[i - 1]).data('song-id')).select();
                }
            },

            /**
             * Clear the current selection on this song list.
             */
            clearSelection() {
                invokeMap(this.$refs.rows, 'deselect');
                this.gatherSelected();
            },

            /**
             * Enable dragging songs by capturing the dragstart event on a table row.
             * Even though the event is triggered on one row only, we'll collect other
             * selected rows, if any, as well.
             *
             * @param {Object} e The event.
             */
            dragStart(songId, e) {
                // If the user is dragging an unselected row, clear the current selection.
                const currentRow = this.getComponentBySongId(songId);
                if (!currentRow.selected) {
                    this.clearSelection();
                    currentRow.select();
                    this.gatherSelected();
                }

                this.$nextTick(() => {
                    // We can opt for something like application/x-koel.text+plain here to sound fancy,
                    // but forget it.
                    const songIds = map(this.selectedSongs, 'id');
                    e.dataTransfer.setData('text/plain', songIds);
                    e.dataTransfer.effectAllowed = 'move';

                    // Set a fancy drop image using our ghost element.
                    const $ghost = $('#dragGhost').text(`${songIds.length} song${songIds.length === 1 ? '' : 's'}`);
                    e.dataTransfer.setDragImage($ghost[0], 0, 0);
                });
            },

            /**
             * Add a "droppable" class and set the drop effect when other songs are dragged over a row.
             *
             * @param {String} songId
             * @param {Object} e The dragover event.
             */
            allowDrop(songId, e) {
                if (this.type !== 'queue') {
                    return;
                }

                $(e.target).parents('tr').addClass('droppable');
                e.dataTransfer.dropEffect = 'move';

                return false;
            },

            /**
             * Perform reordering songs upon dropping if the current song list is of type Queue.
             *
             * @param  {String} songId
             * @param  {Object} e
             */
            handleDrop(songId, e) {
                if (this.type !== 'queue') {
                    return;
                }

                if (!e.dataTransfer.getData('text/plain')) {
                    return false;
                }

                const songs = this.selectedSongs;

                if (!songs.length) {
                    return false;
                }

                queueStore.move(songs, songStore.byId(songId));
                this.removeDroppableState(e);

                return false;
            },

            /**
             * Remove the droppable state (and the styles) from a row.
             *
             * @param  {Object} e
             */
            removeDroppableState(e) {
                return $(e.target).parents('tr').removeClass('droppable');
            },

            openContextMenu(songId, e) {
                // If the user is right-clicking an unselected row,
                // clear the current selection and select it instead.
                const currentRow = this.getComponentBySongId(songId);
                if (!currentRow.selected) {
                    this.clearSelection();
                    currentRow.select();
                    this.gatherSelected();
                }

                this.$nextTick(() => {
                    this.$refs.contextMenu.open(e.pageY, e.pageX);
                });
            },
        },

        events: {
            /**
             * Listen to song:played event to do some logic.
             *
             * @param  {Object} song The current playing song.
             */
            'song:played': function (song) {
                // If the song is at the end of the current displayed items, load more.
                if (this.type === 'queue' && this.items.indexOf(song) >= this.numOfItems) {
                    this.displayMore();
                }

                // Scroll the item into view if it's lost into oblivion.
                if (this.type === 'queue') {
                    const $wrapper = $(this.$els.wrapper);
                    const $row = $wrapper.find(`.song-item[data-song-id="${song.id}"]`);

                    if (!$row.length) {
                        return;
                    }

                    if ($wrapper[0].getBoundingClientRect().top + $wrapper[0].getBoundingClientRect().height <
                        $row[0].getBoundingClientRect().top) {
                        $wrapper.scrollTop($wrapper.scrollTop() + $row.position().top);
                    }
                }

                return true;
            },

            /**
             * Listen to 'filter:changed' event to filter the current list.
             */
            'filter:changed': function (q) {
                this.q = q;
            },

            /**
             * Clears the current list's selection if the user has switched to another view.
             */
            'main-content-view:load': function () {
                this.clearSelection();
            },

            /**
             * Listens to the 'song:selection-changed' dispatched from a child song-item
             * to collect the selected songs.
             */
            'song:selection-changed': function () {
                this.gatherSelected();
            },

            /**
             * Listen to 'song:selection-clear' (often broadcasted from the direct parent)
             * to clear the selected songs.
             */
            'song:selection-clear': function () {
                this.clearSelection();
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    .song-list-wrap {
        position: relative;

        table {
            width: 100%;
        }

        tr.droppable {
            border-bottom-width: 3px;
            border-bottom-color: $colorGreen;
        }

        td, th {
            text-align: left;
            padding: 8px;
            vertical-align: middle;

            &.time {
                width: 72px;
                text-align: right;
            }

            &.track-number {
                min-width: 42px;
            }

            &.play {
                display: none;

                html.touchevents & {
                    display: block;
                    position: absolute;
                    top: 8px;
                    right: 4px;
                }
            }
        }

        th {
            color: $color2ndText;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;

            i {
                color: $colorHighlight;
                font-size: 1.2rem;
            }
        }

        /**
         * Since the Queue screen doesn't allow sorting, we reset the cursor style.
         */
        &.queue th {
            cursor: default;
        }


        @media only screen and (max-width: 768px) {
            table, tbody, tr {
                display: block;
            }

            thead, tfoot {
                display: none;
            }

            tr {
                padding: 8px 32px 8px 4px;
                position: relative;
            }

            td {
                display: inline;
                padding: 0;
                vertical-align: bottom;

                &.album, &.time, &.track-number {
                    display: none;
                }

                &.artist {
                    opacity: .5;
                    font-size: .9rem;
                    padding: 0 4px;
                }
            }
        }
    }
</style>
