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
    </tr>
</template>

<script>
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
        },

        events: {
            /**
             * Listen to 'song:play' event and set the "playing" status.
             * 
             * @param  object song The current playing song.
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

        &.selected {
            background-color: rgba(255, 255, 255, .08);
        }

        &.playing {
            color: $colorHighlight;
        }
    }
</style>
