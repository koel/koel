<template>
    <section id="albumsWrapper">
        <h1 class="heading">
            <span>Albums</span>
        </h1>

        <div class="albums main-scroll-wrap" v-el:wrapper @scroll="scrolling">
            <album-item v-for="item in items
                | orderBy 'name'
                | filterBy q in 'name' 'artist.name'
                | limitBy numOfItems" :album="item"></album-item>
        </div>
    </section>
</template>

<script>
    import albumItem from '../../shared/album-item.vue';
    import infiniteScroll from '../../../mixins/infinite-scroll';
    import albumStore from '../../../stores/album';

    export default {
        mixins: [infiniteScroll],
        components: { albumItem },

        data() {
            return {
                perPage: 9,
                numOfItems: 9,
                state: albumStore.state,
                q: '',
            };
        },

        computed: {
            items() {
                return this.state.albums;
            },
        },

        events: {
            /**
             * When the application is ready, load the first batch of items.
             */
            'koel:ready': function () {
                this.displayMore();
            },

            'koel:teardown': function () {
                this.q = '';
                this.numOfItems = 9;
            },

            'filter:changed': function (q) {
                this.q = q;
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";

    #albumsWrapper {
        .albums {
            @include artist-album-wrapper();
        }
    }
</style>
