<template>
    <tr 
        @dblclick.prevent="play" 
        class="song-item"
        :class="{ 'selected': selected, 'playing': playing }"

    >
        <td class="title">{{ song.title }}</td>
        <td class="artist">{{ song.album.artist.name }}</td>
        <td class="album">{{ song.album.name }}</td>
        <td class="time">{{ song.fmtLength }}</td>
        <td class="check"><input type="checkbox" @click.stop="select($event)"></td>
    </tr>
</template>

<script>
    import $ from 'jquery';
    import playback from '../../services/playback';

    export default {
        props: ['song'],

        data() {
            return {
                playing: false,
            };
        },

        methods: {
            /**
             * Play the song.
             */
            play() {
                playback.play(this.song);
            },

            /**
             * Select a row.
             */
            select(e) {
                if ($(e.target).prop('checked')) {
                    $(e.target).parents('tr').addClass('selected');    
                } else {
                    $(e.target).parents('tr').removeClass('selected');
                }

                // Let the parent listing know to collect the selected songs.
                this.$dispatch('song:selection-changed');
            },
        },

        events: {
            /**
             * Listen to 'song:play' event and set the "playing" status.
             * 
             * @param  {Object} song The current playing song.
             */
            'song:play': function (song) {
                this.playing = this.song.id === song.id;

                return true;
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    .song-item {
        border-bottom: 1px solid $color2ndBgr;

        &:hover {
            background: rgba(255, 255, 255, .05);
        }

        .time {
            color: $color2ndText;
        }

        .title {
            min-width: 192px;
        }

        .check {
            max-width: 32px;
        }

        &.selected {
            background-color: rgba(255, 255, 255, .08);
        }

        &.playing {
            color: $colorHighlight;
        }
    }
</style>
