<template>
    <section id="extra" :class="{ showing: prefs.showExtraPanel }">
        <div class="tabs">
            <div class="header clear">
                <a @click.prevent="currentView = 'lyrics'" 
                    :class="{ active: currentView === 'lyrics' }">Lyrics</a>
                <a @click.prevent="currentView = 'artistInfo'" 
                    :class="{ active: currentView === 'artistInfo' }">Artist</a>
                <a @click.prevent="currentView = 'albumInfo'" 
                    :class="{ active: currentView === 'albumInfo' }">Album</a>
            </div>

            <div class="panes">
                <lyrics v-ref:lyrics v-show="currentView === 'lyrics'"></lyrics>
                <artist-info v-ref:artist-info v-show="currentView === 'artistInfo'"></artist-info>
                <album-info v-ref:album-info v-show="currentView === 'albumInfo'"></album-info>
            </div>
        </div>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';
    import _ from 'lodash';
    
    import lyrics from './lyrics.vue';
    import artistInfo from './artist-info.vue'
    import albumInfo from './album-info.vue'
    import preferenceStore from '../../../stores/preference';
    import songStore from '../../../stores/song';
    
    export default {
        components: { lyrics, artistInfo, albumInfo },

        data() {
            return {
                prefs: preferenceStore.state,
                currentView: 'lyrics',
            };
        },

        ready() {
            if (isMobile.phone) {
                // On a mobile device, we always hide the panel initially regardless of 
                // the saved preference.
                this.prefs.showExtraPanel = false;
                preferenceStore.save();
            }
        },

        methods: {
            /**
             * Reset all applicable child components' states.
             */
            resetChildrenStates() {
                _.invoke(this.$refs, 'resetState');
            },
        },

        events: {
            'main-content-view:load': function (view) {
                // Hide the panel away if a main view is triggered on mobile.
                if (isMobile.phone) {
                    this.prefs.showExtraPanel = false;
                }

                return true;
            },

            'song:played': function (song) {
                this.resetChildrenStates();

                songStore.getInfo(song, () => {
                    this.$broadcast('song:info-loaded', song);
                });
            },

            'koel:teardown': function () {
                this.currentView = 'lyrics';
                this.resetChildrenStates();
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";
        
    #extra {
        flex: 0 0 $extraPanelWidth;
        padding: 24px 16px $footerHeight;
        background: $colorExtraBgr;
        max-height: calc(100vh - #{$headerHeight + $footerHeight});
        overflow: auto;

        // Enable scroll with momentum on touch devices
        overflow-y: scroll; 
        -webkit-overflow-scrolling: touch;
        
        display: none;
        color: $color2ndText;

        &.showing {
            display: block;
        }

        h1 {
            font-weight: $fontWeight_UltraThin;
            font-size: 28px;
            margin-bottom: 16px;
            line-height: 36px;

            @include vertical-center();
            align-items: initial;

            span {
                flex: 1;
                margin-right: 12px;
            }

            a {
                font-size: 14px;

                &:hover {
                    color: $colorHighlight;
                }
            }
        }

        .tabs {
            .header {
                $tabColor: #5c5c5c;
                border-bottom: 1px solid $tabColor;


                a {
                    padding: 8px 12px;
                    margin-left: 4px;
                    border-radius: 4px 4px 0 0;
                    text-transform: uppercase;
                    color: lighten($tabColor, 50%);
                    opacity: .4;
                    border: 1px solid $tabColor;
                    margin-bottom: -1px;
                    float: left;

                    &.active {
                        border-bottom: 1px solid $colorExtraBgr;
                        opacity: 1;
                    }
                }
            }

            .panes {
                padding: 16px 0;
            }
        }

        .more {
            margin-top: 8px;
            border-radius: 3px;
            background: $colorBlue;
            color: #fff;
            padding: 4px 8px;
            display: inline-block;
            text-transform: uppercase;
            font-size: 80%;
        }

        footer {
            margin-top: 24px;
            font-size: 90%;

            a {
                color: #fff; 
                font-weight: $fontWeight_Normal;

                &:hover {
                    color: #b90000;
                }
            }
        }


        @media only screen and (max-device-width : 1024px) {
            position: fixed;
            height: calc(100vh - #{$headerHeight + $footerHeight});
            padding-bottom: $footerHeight; // make sure the footer can never overlap the content
            width: $extraPanelWidth;
            z-index: 5;
            top: $headerHeight;
            right: -100%;
            transition: right .3s ease-in;

            &.showing {
                right: 0;
            }
        }

        @media only screen and (max-device-width : 667px) {
            width: 100%;
        }
    }
</style>
