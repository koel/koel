<template>
    <section id="albumsWrapper">
        <h1 class="heading">
            <span>Albums</span>
            <view-mode-switch :mode.sync="viewMode" for="albums"></view-mode-switch>
        </h1>

        <div class="albums main-scroll-wrap as-{{ viewMode }}" v-el:wrapper @scroll="scrolling">
            <album-item v-for="item in items
                | orderBy 'name'
                | filterBy q in 'name' 'artist.name'
                | limitBy numOfItems" :album="item"></album-item>

            <span class="item filler" v-for="n in 6"></span>

            <to-top-button :showing="showBackToTop"></to-top-button>
        </div>
    </section>
</template>

<script>
    import albumItem from '../../shared/album-item.vue';
    import viewModeSwitch from '../../shared/view-mode-switch.vue';
    import infiniteScroll from '../../../mixins/infinite-scroll';
    import albumStore from '../../../stores/album';

    export default {
        mixins: [infiniteScroll],
        components: { albumItem, viewModeSwitch },

        data() {
            return {
                perPage: 9,
                numOfItems: 9,
                state: albumStore.state,
                q: '',
                viewMode: null,
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

                return true;
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
