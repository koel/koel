<template>
    <div class="side search" id="searchForm" :class="{ showing: showing }">
        <input type="search"
            :class="{ dirty: q }" 
            placeholder="Search" 
            v-model="q" 
            debounce="500"
            v-koel-focus="showing">
    </div>
</template>

<script>
    import isMobile from 'ismobilejs';

    export default {
        data() {
            return {
                q: '',
                showing: !isMobile.phone,
            };
        },

        watch: {
            /**
             * Broadcast a 'filter:changed' event when the filtering query changes.
             * Other components listening to this filter will update its content.
             */
            q() {
                this.$root.$broadcast('filter:changed', this.q);
            },
        },

        events: {
            /**
             * Listen to 'search:toggle' event to show or hide the search form.
             * This should only be triggered on a mobile device.
             */
            'search:toggle': function () {
                this.showing = !this.showing;
            },

            'koel:teardown': function () {
                this.q = '';
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #searchForm {
        @include vertical-center();
        flex: 0 0 256px;
        order: -1;
        background: $colorSearchFormBgr;

        input[type="search"] {
            width: 218px;
            margin-top: 0;
        }

    

        @media only screen 
        and (max-device-width : 667px) {
            z-index: -1;
            position: absolute;
            left: 0;
            background: rgba(0, 0, 0, .8);
            width: 100%;
            padding: 12px;
            top: 0;

            &.showing {
                top: $headerHeight;
                z-index: 100;
            }

            input[type="search"] {
                width: 100%;
            }
        }
    }
</style>
