<template>
    <section id="playlists">
        <h1>Playlists <i class="fa fa-plus-circle control" @click="this.creating = !this.creating"></i></h1>

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

                playlistStore.store(this.newName, [], () => this.newName = '');
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #playlists {
        form.create {
            padding: 8px 16px;

            input[type="text"] {
                width: 100%;
            }
        }
    }
</style>
