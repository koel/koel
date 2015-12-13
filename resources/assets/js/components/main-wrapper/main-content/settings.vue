<template>
    <div id="settingsWrapper">
        <h1 class="heading">
            <span>Settings</span>
        </h1>

        <form @submit.prevent="save" class="main-scroll-wrap">
            <div class="form-row">
                <label for="inputSettingsPath">Media Path</label>
                <p class="help">
                    The <em>absolute</em> path to the server directory containing your media.
                    Koel will scan this directory for songs and extract any available information.<br>
                    Notice: Scanning may take a while, especially if you have a lot of songs, so be patient.<br>
                    The page will refresh after scanning completes.
                </p>

                <input type="text" v-model="state.settings.media_path" id="inputSettingsPath">
            </div>

            <div class="form-row">
                <button type="submit">Scan</button>
            </div>
        </form>
    </div>
</template>

<script>
    import settingStore from '../../../stores/setting';

    export default {
        data() {
            return {
                state: settingStore.state,
            };
        },

        methods: {
            /**
             * Save the settings.
             */
            save() {
                this.$root.toggleOverlay();

                settingStore.update(() => {
                    // Data changed. 
                    // Everything changed. 
                    // It's the time of the oath.
                    // We need refresh the page.
                    // Goodbye.
                    document.location.reload();
                });
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #settingsWrapper {
        input[type="text"] {
            width: 384px;
            margin-top: 12px;
        }

        @media only screen 
        and (max-device-width : 667px) 
        and (orientation : portrait) {
            input[type="text"] {
                width: 100%;
            }
        }
    }
</style>
