<template>
    <div class="to-top-btn-wrapper" v-show="showing" transition="fade">
        <button @click="$parent.scrollToTop()">
            <i class="fa fa-arrow-circle-up"></i>
            Top
        </button>
    </div>
</template>

<script>
    export default {
        props: ['showing'],
    };
</script>

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    .to-top-btn-wrapper {
        position: fixed;
        width: 100%;
        bottom: $footerHeight + 16px;
        left: 0;
        text-align: center;
        z-index: 9999;

        &.fade-transition {
            opacity: 1;
            transition: opacity .5s;
        }

        &.fade-enter {
            opacity: 0;
        }

        &.fade-leave {
            opacity: 0;
        }

        button {
            border-radius: 18px;
            padding: 8px 16px;
            background: rgba(0, 0, 0, .5);
            border: 1px solid $colorMainText;

            i {
                margin-right: 4px;
            }
        }
    }

    @media screen and (min-width: 376px) {
        .to-top-btn-wrapper {
            display: none;
        }
    }
</style>
