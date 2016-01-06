<template>
    <section id="mainContent">
        <div class="translucent" :style="{ backgroundImage: albumCover ? 'url(' + albumCover + ')' : 'none' }"></div>
        <songs v-show="view == 'songs'"></songs>
        <queue v-show="view == 'queue'"></queue>
        <albums v-show="view == 'albums'"></albums>
        <artists v-show="view == 'artists'"></artists>
        <users v-show="view == 'users'"></users>
        <settings v-show="view == 'settings'"></settings>
        <playlist v-show="view == 'playlist'"></playlist>
        <favorites v-show="view == 'favorites'"></favorites>
        <profile v-show="view == 'profile'"></profile>
    </section>
</template>

<script>
    import albums from './albums.vue';
    import artists from './artists.vue';
    import songs from './songs.vue';
    import settings from './settings.vue';
    import users from './users.vue';
    import queue from './queue.vue';
    import playlist from './playlist.vue';
    import favorites from './favorites.vue';
    import profile from './profile.vue';
    import albumStore from '../../../stores/album';

    export default {
        components: { albums, artists, songs, settings, users, queue, playlist, favorites, profile },

        props: ['songs', 'queue', 'albums', 'artists', 'users', 'settings', 'playlist', 'favorites', 'profile'],

        data() {
            return {
                view: 'queue', // The default view
                albumCover: null,
            };
        },

        events: {
            'main-content-view:load': function (view) {
                this.view = view;

                return true;
            },

            /**
             * When a new song is played, find it cover for the translucent effect.
             * 
             * @param  object song
             * 
             * @return true
             */
            'song:play': function (song) {
                this.albumCover = song.album.cover ===  albumStore.stub.cover ? null : song.album.cover;

                return true;
            }
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #mainContent {
        flex: 1;
        position: relative;

        > section {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;

            .main-scroll-wrap {
                padding: 24px;
                overflow: auto;
                flex: 1;

                // Enable scroll with momentum on touch devices
                overflow-y: scroll; 
                -webkit-overflow-scrolling: touch;
            }
        }

        h1.heading {
            font-weight: $fontWeight_UltraThin;
            font-size: 48px;
            padding: 12px 24px;
            border-bottom: 1px solid $color2ndBgr;
            min-height: 90px;
            position: relative;
            align-items: center;
            align-content: stretch;
            display: flex;

            span:first-child {
                flex: 1;
            }

            .buttons {
                text-align: right;
                z-index: 2;

                @include button-group();

                .add-to {
                    background-color: $colorGreen !important;

                    &:hover {
                        background-color: darken($colorGreen, 10%) !important;
                    }
                }
            }

            input[type="search"] {
                width: 128px;
                transition: width .3s;

                &:focus {
                    width: 192px;
                }
            }
        }

        .translucent {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            -webkit-filter: blur(20px);
            opacity: .07;
            z-index: 0;
            overflow: hidden;
            background-size: cover;
            background-position: center;
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000;
            pointer-events: none;
        }



        @media only screen 
        and (max-device-width : 768px) {
            h1.heading {
                font-size: 18px;
                min-height: 0;
                line-height: 24px;
                text-align: center;
                flex-direction: column;

                .toggler {
                    font-size: 14px;
                    margin-left: 4px;
                    color: $colorHighlight;
                }

                .buttons, input[type="search"] {
                    justify-content: center;
                    margin-top: 8px;
                }
            }

            > section {
                .main-scroll-wrap {
                    padding: 12px;
                }    
            }
        }
    }
</style>
