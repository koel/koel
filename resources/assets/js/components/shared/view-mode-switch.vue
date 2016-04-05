<template>
    <span class="view-modes">
        <a :class="{ active: mode === 'thumbnails' }"
            title="View as thumbnails"
            @click.prevent="setMode('thumbnails')"><i class="fa fa-th-large"></i></a>
        <a :class="{ active: mode === 'list' }"
            title="View as list"
            @click.prevent="setMode('list')"><i class="fa fa-list"></i></a>
    </span>
</template>

<script>
    import preferences from '../../stores/preference';
    import isMobile from 'ismobilejs';

    export default {
        props: ['mode', 'for'],

        computed: {
            /**
             * The preference key for local storage for persistent mode.
             *
             * @return {string}
             */
            preferenceKey() {
                return `${this.for}ViewMode`;
            },
        },

        methods: {
            setMode(mode) {
                preferences[this.preferenceKey] = this.mode = mode;
            },
        },

        events: {
            'koel:ready': function () {
                this.mode = preferences[this.preferenceKey];

                // If the value is empty, we set a default mode.
                // On mobile, the mode should be 'listing'.
                // For desktop, 'thumbnails'.
                if (!this.mode) {
                    this.mode = isMobile.phone ? 'list' : 'thumbnails';
                }
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    .view-modes {
        display: flex;
        flex: 0 0 64px;
        border: 1px solid rgba(255, 255, 255, .2);
        height: 28px;
        border-radius: 5px;
        overflow: hidden;

        a {
            width: 50%;
            text-align: center;
            line-height: 26px;
            font-size: 14px;

            &.active {
                background: #fff;
                color: #111;
            }
        }

        @media only screen and(max-width: 768px) {
            flex: 0;
            width: 64px;
            margin-top: 8px;
        }
    }
</style>
