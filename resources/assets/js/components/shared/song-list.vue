<template>
    <div class="song-list-wrap main-scroll-wrap {{ type }}" tabindex="1" v-el:wrapper 
        @scroll="scrolling" 
        @keydown.delete.prevent.stop="handleDelete"
        @keydown.8.prevent.stop="handleDelete"
        @keydown.enter.prevent.stop="handleEnter"
        @keydown.a.prevent="handleA"
    >
        <table v-show="items.length">
            <thead>
                <tr>
                    <th @click="sort('title')">Title
                        <i class="fa fa-angle-down" v-show="sortKey === 'title' && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortKey === 'title' && order < 0"></i>
                    </th>
                    <th @click="sort('album.artist.name')">Artist
                        <i class="fa fa-angle-down" v-show="sortKey === 'album.artist.name' && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortKey === 'album.artist.name' && order < 0"></i>
                    </th>
                    <th @click="sort('album.name')">Album
                        <i class="fa fa-angle-down" v-show="sortKey === 'album.name' && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortKey === 'album.name' && order < 0"></i>
                    </th>
                    <th @click="sort('fmtLength')" class="time">Time
                        <i class="fa fa-angle-down" v-show="sortKey === 'fmtLength' && order > 0"></i>
                        <i class="fa fa-angle-up" v-show="sortKey === 'fmtLength' && order < 0"></i>
                    </th>
                    <th class="check"></th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="item in items 
                        | caseInsensitiveOrderBy sortKey order
                        | filterBy q in 'title' 'album.name' 'album.artist.name' 
                        | limitBy numOfItems"
                    is="song-item" 
                    data-song-id="{{ item.id }}" 
                    :song="item" 
                    @click="rowClick($event)"
                    draggable="true"
                    @dragstart="dragStart"
                >
                </tr>
            </tbody>
        </table>
    </div>
    
</template>

<script>
    import _ from 'lodash';
    import isMobile from 'ismobilejs';
    import $ from 'jquery';
    
    import songItem from './song-item.vue';
    import infiniteScroll from '../../mixins/infinite-scroll';
    import playlistStore from '../../stores/playlist';
    import queueStore from '../../stores/queue';
    import songStore from '../../stores/song';
    import favoriteStore from '../../stores/favorite';
    import playback from '../../services/playback';

    export default {
        props: ['items', 'type', 'playlist', 'selectedSongs'],
        mixins: [infiniteScroll],
        components: { songItem },

        data() {
            return {
                lastSelectedRow: null,
                q: '', // The filter query
                sortKey: 'title',
                order: 1,
            };
        },

        watch: {
            /**
             * Watch the items, so that we can always make sure a queue is not sorted in any ways.
             */
            items() {
                if (this.type === 'queue') {
                    this.sortKey = '';
                }
            },
        },

        methods: {
            /**
             * Handle sorting the song list.
             * 
             * @param  {String} key The sort key. Can be 'title', 'album', 'artist', or 'fmtLength'
             */
            sort(key) {
                // We don't allow sorting in the Queue screen.
                // (It makes little sense if we do, since the queue is shuffled with each population).
                if (this.type === 'queue') {
                    return;
                }

                this.sortKey = key;
                this.order = 0 - this.order;
            },

            /**
             * Execute the corresponding reaction(s) when the user presses Delete.
             */
            handleDelete() {
                var songs = this.selectedSongs;

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
                        playlistStore.removeSongs(this.playlist, songs)
                        break;
                    default: 
                        break;
                }

                this.clearSelection();
            },

            /**
             * Execute the corresponding reaction(s) when the user presses Enter.
             */
            handleEnter(e) {
                var songs = this.selectedSongs;

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
                        if (e.shiftKey) {
                            queueStore.queue(songs, false);
                        } else {
                            queueStore.queue(songs, false, false);
                        }

                        Vue.nextTick(() => {
                            this.$root.loadMainView('queue');

                            if (e.ctrlKey || e.metaKey || songs.length == 1) {
                                playback.play(songs[0]);
                            }
                        });

                        break;
                }

                this.clearSelection();
            },

            /**
             * Capture A keydown event and select all if applicable.
             */
            handleA(e) {
                if (!e.metaKey && !e.ctrlKey) {
                    return;
                }

                $(this.$els.wrapper)
                    .find('.song-item').addClass('selected')
                    .find(':checkbox').prop('checked', true);
                this.gatherSelected();
            },

            /**
             * Gather all selected songs.
             * 
             * @return {Array} An array of Song objects
             */
            gatherSelected() {
                var ids = _.map($(this.$els.wrapper).find('.song-item.selected'), row => $(row).data('song-id'));

                this.selectedSongs = songStore.byIds(ids);
            },


            /**
             * -----------------------------------------------------------
             * The next four methods are to deal with selection.
             * Don't even try to understand it. It just works.
             * 
             * Credits: http://stackoverflow.com/a/17966381/794641 by andyb
             * -----------------------------------------------------------
             */

            rowClick(e) {
                var $target = $(e.target);
                var row = $target.is('tr') ? $target[0] : $target.parents('tr')[0];

                // If we're on a touch device, tapping a row means playing right away.
                if (isMobile.any) {
                    playback.play(songStore.byId($(row).data('song-id')));

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
                
                    if (e.shiftKey) {
                        this.selectRowsBetweenIndexes([this.lastSelectedRow.rowIndex, row.rowIndex])
                    }
                }

                this.gatherSelected();
            },

            toggleRow(row) {
                $(row).find(':checkbox').click();
                this.lastSelectedRow = row;
            },

            selectRowsBetweenIndexes(indexes) {
                indexes.sort((a, b) => a - b);

                var rows = $(this.lastSelectedRow).parents('tbody').find('tr');

                for (var i = indexes[0]; i <= indexes[1]; ++i) {
                    $(rows[i-1]).addClass('selected')
                        .find(':checkbox').prop('checked', true);
                }
            },

            clearSelection() {
                this.selectedSongs = [];
                $(this.$els.wrapper)
                    .find('.song-item.selected').removeClass('selected')
                    .find(':checked').prop('checked', false);
            },

            /**
             * Enable dragging songs by capturing the dragstart event on a table row.
             * Even though the event is triggered on one row only, we'll collect other
             * selected rows, if any, as well.
             */
            dragStart(e) {
                // Select the current target as well.
                $(e.target).addClass('selected');

                // We can opt for something like application/x-koel.text+plain here to sound fancy,
                // but forget it.
                e.dataTransfer.setData('text/plain', _.pluck(this.selectedSongs, 'id'));
                e.dataTransfer.effectAllowed = 'move';

                // Set a fancy icon
                var dragIcon = document.createElement('img');
                dragIcon.src = '/public/img/drag-icon.png';
                dragIcon.width = 16;

                e.dataTransfer.setDragImage(dragIcon, -10, -10);
            }
        },

        events: {
            /**
             * Listen to queue:select-rows event and mark a range of song-item rows as selected
             * 
             * @param  {Array} range An array in the format of [startIndex, stopIndex]
             */
            'queue:select-rows': function (range) {
                if (this.type != 'queue') {
                    return;
                }

                var rows = $(this.$els.wrapper).find('.song-item');

                for (i = range[0]; i <= range[1]; ++i) {
                    $(rows[i]).addClass('selected');
                }
            },

            /**
             * Listen to song:play event to do some logic.
             * 
             * @param  {Object} song The current playing song.
             */
            'song:play': function (song) {
                // If the song is at the end of the current displayed items, load more.
                if (this.type === 'queue' && this.items.indexOf(song) >= this.numOfItems) {
                    this.displayMore();
                }

                // Scroll the item into view if it's lost into oblivion.
                if (this.type === 'queue') {
                    var $wrapper = $(this.$els.wrapper);
                    var $row = $wrapper.find(`.song-item[data-song-id="${song.id}"]`);
                    
                    if (!$row.length) {
                        return;
                    }

                    if ($wrapper[0].getBoundingClientRect().top + $wrapper[0].getBoundingClientRect().height
                        < $row[0].getBoundingClientRect().top) {
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
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    .song-list-wrap {
        position: relative;

        table {
            width: 100%;
        }

        td, th {
            text-align: left;
            padding: 8px;
            vertical-align: middle;

            &.time {
                width: 72px;
                text-align: right;
            }

            &.check {
                display: none;
            }
        }

        th {
            color: $color2ndText;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;

            i {
                color: $colorHighlight;
                font-size: 120%;
            }
        }

        /**
         * Since the Queue screen doesn't allow sorting, we reset the cursor style.
         */
        &.queue th {
            cursor: default;
        }


        @media only screen 
        and (max-device-width : 768px) {
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

                &.album, &.time {
                    display: none;
                }

                &.artist {
                    opacity: .5;
                    font-size: 90%;
                    padding: 0 4px;
                }

                &.check {
                    display: block;
                    position: absolute;
                    top: 8px;
                    right: 0;
                }
            }
        }
    }
</style>
