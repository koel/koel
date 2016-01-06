<template>
    <header id="mainHeader">
        <h1 class="brand ir">koel</h1>
        <span class="hamburger" @click="toggleSidebar">
            <i class="fa fa-align-justify"></i>
        </span>
        <span class="magnifier" @click="toggleSearchForm">
            <i class="fa fa-search"></i>
        </span>
        <search-form></search-form>
        <user-badge></user-badge>
    </header>

</template>

<script>
    import searchForm from './search-form.vue';
    import userBadge from './user-badge.vue';

    export default {
        components: { searchForm, userBadge },

        methods: {
            /**
             * No I'm not documenting this.
             */
            toggleSidebar() {
                this.$root.$broadcast('sidebar:toggle');
            },

            /**
             * or this.
             */
            toggleSearchForm() {
                this.$root.$broadcast('search:toggle');
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #mainHeader {
        height: $headerHeight;
        background: $color2ndBgr;
        display: flex;
        border-bottom: 1px solid $colorMainBgr;

        h1.brand {
            flex: 1;

            @include vertical-center();
        }

        .hamburger, .magnifier {
            font-size: 140%;
            @include vertical-center();
            flex: 0 0 48px;
            order: -1;
            display: none;
        }

        @media only screen 
        and (max-device-width : 667px) {
            display: flex;
            align-content: stretch;
            justify-content: flext-start;

            .hamburger, .magnifier {
                display: flex;
            }

            h1.brand {
                flex: 1;
                color: $colorMainText;
                font: 22px Roboto;
                font-weight: $fontWeight_UltraThin;
            }
        }
    }
</style>
