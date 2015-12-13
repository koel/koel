<template>
    <div id="overlay">
        <sound-bar></sound-bar>
        <span class="gnr">Just a little patience…</span>
    </div>
</template>

<script>
    import soundBar from './sound-bar.vue';

    export default {
        components: { soundBar },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #overlay {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 9999;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 1);

        @include vertical-center();

        .gnr {
            opacity: .7;
        }
    }
</style>
