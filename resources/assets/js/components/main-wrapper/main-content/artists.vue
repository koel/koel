<template>
    <section id="artistsWrapper">
        <h1 class="heading">
            <span>Artists</span>
            <view-mode-switch :mode.sync="viewMode" :for="'artists'"></view-mode-switch>
        </h1>

        <div class="artists main-scroll-wrap as-{{ viewMode }}" v-el:wrapper @scroll="scrolling">
            <artist-item v-for="item in items
                | filterBy q in 'name'
                | limitBy numOfItems" :artist="item"></artist-item>

            <span class="item" v-for="n in 6"></span>
        </div>
    </section>
</template>

<script>
    import artistItem from '../../shared/artist-item.vue';
    import viewModeSwitch from '../../shared/view-mode-switch.vue';
    import infiniteScroll from '../../../mixins/infinite-scroll';
    import artistStore from '../../../stores/artist';

    export default {
        mixins: [infiniteScroll],

        components: { artistItem, viewModeSwitch },

        data() {
            return {
                perPage: 9,
                numOfItems: 9,
                state: artistStore.state,
                q: '',
                viewMode: null,
            };
        },

        computed: {
            items() {
                return this.state.artists;
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

    #artistsWrapper {
        .artists {
            @include artist-album-wrapper();
        }
    }
</style>
