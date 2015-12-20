<template>
    <nav class="side side-nav" id="sidebar" :class="{ showing: showing }">
        <section class="music">
            <h1>Your Music</h1>

            <ul class="menu">
                <li>
                    <a class="queue" :class="[currentView == 'queue' ? 'active' : '']" 
                        @click.prevent="$root.loadMainView('queue')">Current Queue</a>
                </li>
                <li>
                    <a class="songs" :class="[currentView == 'songs' ? 'active' : '']" 
                        @click.prevent="$root.loadMainView('songs')">All Songs</a>
                </li>
                <li>
                    <a class="albums" :class="[currentView == 'albums' ? 'active' : '']" 
                        @click.prevent="$root.loadMainView('albums')">Albums</a>
                </li>
                <li>
                    <a class="artists" :class="[currentView == 'artists' ? 'active' : '']" 
                        @click.prevent="$root.loadMainView('artists')">Artists</a>
                </li>
            </ul>
        </section>

        <playlists :current-view="currentView"></playlists>
        
        <section v-if="user.current.is_admin" class="manage">
            <h1>Manage</h1>

            <ul class="menu">
                <li>
                    <a class="settings" :class="[currentView == 'settings' ? 'active' : '']" 
                        @click.prevent="$root.loadMainView('settings')">Settings</a>
                    </li>
                <li>
                    <a class="users" :class="[currentView == 'users' ? 'active' : '']"
                        @click.prevent="$root.loadMainView('users')">Users</a>
                </li>
            </ul>
        </section>
    </nav>
</template>

<script>
    import isMobile from 'ismobilejs';
    
    import playlists from './playlists.vue';
    import userStore from '../../../stores/user';

    export default {
        components: { playlists },

        data() {
            return {
                currentView: 'queue',
                user: userStore.state,
                showing: !isMobile.phone,
            };
        },

        events: {
            'main-content-view:load': function (view) {
                this.currentView = view;

                // Hide the sidebar if on mobile
                if (isMobile.phone) {
                    this.showing = false;
                }
            },

            /**
             * Listen to sidebar:toggle event to show or hide the sidebar.
             * This should only be triggered on a mobile device.
             */
            'sidebar:toggle': function () {
                this.showing = !this.showing;
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";
    
    #sidebar {
        flex: 0 0 256px;
        background-color: $colorSidebarBgr;
        padding: 22px 0 0;
        max-height: calc(100vh - #{$headerHeight + $footerHeight});
        overflow: auto;

        // Enable scroll with momentum on touch devices
        overflow-y: scroll; 
        -webkit-overflow-scrolling: touch;

        section {
            margin-bottom: 32px;

            h1 {
                text-transform: uppercase;
                letter-spacing: 1px;
                padding: 0 16px;
                margin-bottom: 12px;

                i {
                    float: right;
                }
            }

            a {
                display: block;
                height: 36px;
                line-height: 36px;
                padding: 0 12px 0 16px;
                border-left: 4px solid transparent;

                &.active, &:hover {
                    border-left-color: $colorHighlight;
                    color: $colorLinkHovered;
                    background: rgba(255, 255, 255, .05);
                }

                &:hover {
                    border-left-color: darken($colorHighlight, 20%);
                }

                &::before {
                    width: 24px;
                    display: inline-block;
                    font-family: FontAwesome;
                }

                &.queue::before {
                    content: "\f0cb";
                }

                &.songs::before {
                    content: "\f001";
                }

                &.albums::before {
                    content: "\f152";
                }

                &.artists::before {
                    content: "\f130";
                }

                &.settings::before {
                    content: "\f013";
                }

                &.users::before {
                    content: "\f0c0";
                }
            }
        }



        @media only screen 
        and (max-device-width : 667px) 
        and (orientation : portrait) {
            position: fixed;
            height: calc(100vh - #{$headerHeight + $footerHeight});
            padding-bottom: $footerHeight; // make sure the footer can never overlap the content
            width: 100%;
            z-index: 99;
            top: $headerHeight;
            left: -100%;
            transition: left .3s ease-in;

            &.showing {
                left: 0;
            }
        }
    }
</style>

