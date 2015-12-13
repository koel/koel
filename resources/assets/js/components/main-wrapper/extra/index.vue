<template>
    <section id="extra" :class="{ showing: prefs.showExtraPanel }">
        <h1>Lyrics</h1>

        <div class="content">
            <lyrics></lyrics>
        </div>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';
    
    import lyrics from './lyrics.vue';
    import preferenceStore from '../../../stores/preference';
    
    export default {
        components: { lyrics },

        data() {
            return {
                prefs: preferenceStore.state,
            };
        },

        ready() {
            if (isMobile.phone) {
                // On a mobile device, we always hide the panel initially regardless of 
                // the saved preference.
                this.prefs.showExtraPanel = false;
                preferenceStore.save();
            }
        },

        events: {
            'main-content-view:load': function (view) {
                // Hide the panel away if a main view is triggered on mobile.
                if (isMobile.phone) {
                    this.prefs.showExtraPanel = false;
                }
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";
        
    #extra {
        flex: 0 0 334px;
        padding: 16px 16px $footerHeight;
        background: $colorExtraBgr;
        max-height: calc(100vh - #{$headerHeight + $footerHeight});
        overflow: auto;
        display: none;

        &.showing {
            display: block;
        }

        h1 {
            font-weight: $fontWeight_UltraThin;
            font-size: 32px;
            margin-bottom: 16px;
            line-height: 64px;
        }


        @media only screen 
        and (max-device-width : 1024px) {
            position: fixed;
            height: calc(100vh - #{$headerHeight + $footerHeight});
            padding-bottom: $footerHeight; // make sure the footer can never overlap the content
            width: 334px;
            z-index: 5;
            top: $headerHeight;
            right: -100%;
            transition: right .3s ease-in;

            &.showing {
                right: 0;
            }
        }

        @media only screen 
        and (max-device-width : 667px) 
        and (orientation : portrait) {
            width: 100%;
        }
    }
</style>
