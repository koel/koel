<template>
    <section id="artistsWrapper">
        <h1 class="heading">
            <span>
                Artists
                <i class="fa fa-chevron-down toggler" 
                    v-show="isPhone && !showingControls" 
                    @click="showingControls = true"></i>
                <i class="fa fa-chevron-up toggler" 
                    v-show="isPhone && showingControls" 
                    @click.prevent="showingControls = false"></i>
            </span>
            <input 
                v-show="!isPhone || showingControls"
                type="search" 
                v-model="q" 
                :class="{ dirty: q }" 
                debounce="100" 
                placeholder="Search">
        </h1>
    
        <div class="artists main-scroll-wrap" v-el:wrapper @scroll="scrolling">
            <artist-item v-for="item in items 
                | filterBy q in 'name' 
                | limitBy numOfItems" :artist="item"></artist-item>

            <!-- 
            Add several more items to make sure the last row is left-aligned.
            Credits: http://codepen.io/dalgard/pen/Dbnus
            -->
            <span class="item"></span>
            <span class="item"></span>
            <span class="item"></span>
            <span class="item"></span>
            <span class="item"></span>
            <span class="item"></span>
            <span class="item"></span>
            <span class="item"></span>
        </div>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import artistItem from '../../shared/artist-item.vue';
    import infiniteScroll from '../../../mixins/infinite-scroll';
    import artistStore from '../../../stores/artist';

    export default {
        mixins: [infiniteScroll],

        components: { artistItem },

        data() {
            return {
                perPage: 9,
                numOfItems: 9,
                state: artistStore.state,
                q: '',
                isPhone: isMobile.phone,
                showingControls: false,
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
            },

            'koel:teardown': function () {
                this.q = '';
                this.numOfItems = 9;
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #artistsWrapper {
        .artists {
            @include artist-album-card();
        }
    }
</style>
