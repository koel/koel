<template>
    <section id="artistsWrapper">
        <h1 class="heading">
            <span>Artists</span>
            <view-mode-switch :mode.sync="viewMode" for="artists"></view-mode-switch>
        </h1>

        <div class="artists main-scroll-wrap as-{{ viewMode }}" @scroll="scrolling">
            <artist-item v-for="item in displayedItems" :artist="item"></artist-item>
            <span class="item filler" v-for="n in 6"></span>
            <to-top-button :showing="showBackToTop"></to-top-button>
        </div>
    </section>
</template>

<script>
    import { filterBy, limitBy } from '../../../utils';
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
                q: '',
                viewMode: null,
            };
        },

        computed: {
            displayedItems() {
                return limitBy(
                    filterBy(artistStore.all, this.q, 'name'),
                    this.numOfItems
                );
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
