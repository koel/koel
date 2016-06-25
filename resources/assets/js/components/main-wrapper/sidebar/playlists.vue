<template>
    <section id="playlists">
        <h1>Playlists
            <i class="fa fa-plus-circle control create"
                :class="{ creating: creating }"
                @click="creating = !creating"></i>
        </h1>

        <form v-show="creating" @submit.prevent="store" class="create">
            <input type="text"
                @keyup.esc.prevent="creating = false"
                v-model="newName"
                v-koel-focus="creating"
                placeholder="↵ to save"
                required
            >
        </form>

        <ul class="menu">
            <playlist-item
                type="favorites"
                :playlist="{ name: 'Favorites', songs: favoriteState.songs }"></playlist-item>
            <playlist-item
                v-for="playlist in playlistState.playlists"
                type="playlist"
                :playlist="playlist"></playlist-item>
        </ul>
    </section>
</template>

<script>
    import { last } from 'lodash';

    import { loadPlaylistView } from '../../../utils';
    import playlistStore from '../../../stores/playlist';
    import favoriteStore from '../../../stores/favorite';

    import playlistItem from './playlist-item.vue';

    export default {
        props: ['currentView'],
        components: { playlistItem },

        data() {
            return {
                playlistState: playlistStore.state,
                favoriteState: favoriteStore.state,
                creating: false,
                newName: '',
            };
        },

        methods: {
            /**
             * Store/create a new playlist.
             */
            store() {
                this.creating = false;

                playlistStore.store(this.newName, [], () => {
                    this.newName = '';
                    this.$nextTick(() => {
                        loadPlaylistView(last(this.playlistState.playlists));
                    });
                });
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";

    #playlists {
        .control.create {
            margin-top: 2px;
            font-size: 16px;
            transition: .3s;

            &.creating {
                transform: rotate(135deg);
            }
        }

        form.create {
            padding: 8px 16px;

            input[type="text"] {
                width: 100%;
            }
        }
    }
</style>
