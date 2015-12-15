<template>
    <div id="overlay" v-show="state.showing" class="{{ state.type }}">
        <div class="display">
            <sound-bar v-show="state.type === 'loading'"></sound-bar>
            <i class="fa fa-exclamation-circle" v-show="state.type === 'error'"></i>
            <i class="fa fa-exclamation-triangle" v-show="state.type === 'warning'"></i>
            <i class="fa fa-info-circle" v-show="state.type === 'info'"></i>
            <i class="fa fa-check-circle" v-show="state.type === 'success'"></i>

            <span>{{{ state.message }}}</span>
        </div>

        <button v-show="state.dismissable" @click.prevent="state.showing = false">Close</button>
    </div>
</template>

<script>
    import soundBar from './sound-bar.vue';

    export default {
        props: ['state'],
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
        flex-direction: column;

        .display {
            @include vertical-center();

            i {
                margin-right: 6px;
            }
        }

        button {
            font-size: 12px;
            margin-top: 16px;
        }

        &.error {
            color: $colorRed;
        }

        &.success {
            color: $colorGreen;
        }

        &.info {
            color: $colorBlue;
        }

        &.loading {
            color: $color2ndText;
        }

        &.warning {
            color: $colorOrange;
        }
    }
</style>
