<template>
    <div id="queueWrapper">
        <h1 class="heading">
            <span title="That's a freaking lot of U's and E's">Current Queue
                <i class="fa fa-chevron-down toggler" 
                    v-show="isPhone && !showingControls" 
                    @click="showingControls = true"></i>
                <i class="fa fa-chevron-up toggler" 
                    v-show="isPhone && showingControls" 
                    @click.prevent="showingControls = false"></i>
            </span>

            <div class="buttons" v-show="!isPhone || showingControls">
                <button 
                    class="play-shuffle" 
                    @click.prevent="shuffle" 
                    v-if="state.songs.length > 1 && selectedSongs.length < 2"
                >
                    <i class="fa fa-random"></i> All
                </button>
                <button class="play-shuffle" @click.prevent="shuffleSelected" v-if="selectedSongs.length > 1">
                    <i class="fa fa-random"></i> Selected
                </button>
                <button class="save" @click.prevent="saving = !saving" v-if="state.songs.length > 1">
                    {{ saving ? 'Cancel' : 'Save' }}
                </button>
                <button class="clear" @click.prevent="clear" v-if="state.songs.length">Clear</button>

                <form class="form-save form-simple" v-show="saving" @submit.prevent="save">
                    <input type="text" 
                        @keyup.esc.prevent="saving = false"
                        v-model="playlistName" 
                        v-koel-focus="saving"
                        placeholder="Playlist name"
                        required>
                    <button type="submit" id="saveQueueSubmit"><i class="fa fa-save"></i></button>
                </form>
            </div>
        </h1>

        <song-list :items="state.songs" :selected-songs.sync="selectedSongs" type="queue"></song-list>
    </div>
</template>

<script>
    import _ from 'lodash';
    import isMobile from 'ismobilejs';
    
    import songList from '../../shared/song-list.vue';
    import playlistStore from '../../../stores/playlist';
    import queueStore from '../../../stores/queue';
    import playback from '../../../services/playback';
    import shuffleSelectedMixin from '../../../mixins/shuffle-selected';
    
    export default {
        mixins: [shuffleSelectedMixin],

        components: { songList },

        data() {
            return {
                state: queueStore.state,
                saving: false,
                playlistName: '',
                isPhone: isMobile.phone,
                showingControls: false,
            };
        },

        watch: {
            /**
             * Watch the number of songs currently queued.
             * If we don't have any queuing song, the "Save to Playlist" form shouldn't be open.
             */
            'state.songs': function () {
                if (!queueStore.all().length) {
                    this.saving = false;
                }
            },
        },

        methods: {
            /**
             * Shuffle the current queue.
             */
            shuffle() {
                playback.queueAndPlay(queueStore.shuffle());
            },

            /**
             * Clear the queue.
             */
            clear() {
                queueStore.clear();
            },

            /**
             * Save the WHOLE queue as a playlist.
             * As of current we don't have selective save.
             */
            save() {
                this.playlistName = this.playlistName.trim();
                
                if (!this.playlistName) {
                    return;
                }

                playlistStore.store(this.playlistName, _.pluck(queueStore.all(), 'id'), () => {
                    this.playlistName = '';
                    this.saving = false;
                });
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #queueWrapper {
        .none {
            color: $color2ndText;
            margin-top: 16px;
        }


        button.play-shuffle {
            i {
                margin-right: 0 !important;
            }
        }

        button.clear {
            background-color: $colorBlue !important;

            &:hover {
                background-color: darken($colorBlue, 10%) !important;
            }
        }

        button.save {
            background-color: $colorGreen !important;

            &:hover {
                background-color: darken($colorGreen, 10%) !important;
            }
        }

        .form-save {
            position: absolute;
            bottom: -50px;
            left: 0;
            background: $colorGreen;
            padding: 8px;
            width: 100%;
            border-radius: 5px;
            display: flex;
            justify-content: center;
            align-items: center;

            &::before {
                display: block;
                content: " ";
                width: 0;
                height: 0;
                border-left: 10px solid transparent;
                border-right: 10px solid transparent;
                border-bottom: 10px solid $colorGreen;
                position: absolute;
                top: -7px;
                left: calc(50% - 10px);
            }

            input[type="text"] {
                width: 100%;
                border-radius: 5px 0 0 5px;
                height: 28px;
            }

            button#saveQueueSubmit {
                margin-top: 0;
                border-radius: 0 5px 5px 0;
                height: 28px;
                margin-left: -2px;
            }
        }

        @media only screen 
        and (max-device-width : 667px) 
        and (orientation : portrait) {
        }
    }
</style>
