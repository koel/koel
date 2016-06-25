<template>
    <div class="side search" id="searchForm" :class="{ showing: showing }">
        <input type="search"
            :class="{ dirty: q }"
            @input="filter"
            placeholder="Search"
            v-model="q"
            v-koel-focus="showing">
    </div>
</template>

<script>
    import isMobile from 'ismobilejs';
    import { debounce } from 'lodash';

    import { event } from '../../utils';

    export default {
        name: 'site-header--search-form',

        data() {
            return {
                q: '',
                showing: !isMobile.phone,
            };
        },

        methods: {
            /**
             * Limit the filter's execution rate using lodash's debounce.
             */
            filter: debounce(function () {
                event.emit('filter:changed', this.q);
            }, 200),
        },

        created() {
            event.on('search:toggle', () => {
                this.showing = !this.showing;
            });

            event.on('koel:teardown', () => {
                this.q = '';
                this.filter();
            })
        },
    };
</script>

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    #searchForm {
        @include vertical-center();
        flex: 0 0 256px;
        order: -1;
        background: $colorSearchFormBgr;

        input[type="search"] {
            width: 218px;
            margin-top: 0;
        }

        @media only screen and (max-width : 667px) {
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
