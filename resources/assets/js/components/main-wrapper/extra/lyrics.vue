<template>
    <div id="lyrics">{{{ lyrics }}}</div>
</template>

<script>
    import songStore from '../../../stores/song';

    export default {
        data() {
            return {
                lyrics: '',
            };
        },

        events: {
            /**
             * Whenever a song is played, get its lyrics from store to display.
             * 
             * @param  object song The currently played song
             * 
             * @return true
             */
            'song:play': function (song) {
                this.lyrics = 'Loading…';

                songStore.getLyrics(song, () => {
                    this.lyrics = song.lyrics;
                });

                return true;
            },
        },
    }
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";
    
    #lyrics {
        color: $color2ndText;
    }
</style>
