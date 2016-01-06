<template>
    <section id="settingsWrapper">
        <h1 class="heading">
            <span>Settings</span>
        </h1>

        <form @submit.prevent="save" class="main-scroll-wrap">
            <div class="form-row">
                <label for="inputSettingsPath">Media Path</label>
                <p class="help">
                    The <em>absolute</em> path to the server directory containing your media.
                    Koel will scan this directory for songs and extract any available information.<br>
                    Scanning may take a while, especially if you have a lot of songs, so be patient.
                </p>

                <input type="text" v-model="state.settings.media_path" id="inputSettingsPath">
            </div>

            <div class="form-row">
                <button type="submit">Scan</button>
            </div>
        </form>
    </section>
</template>

<script>
    import settingStore from '../../../stores/setting';
    import utils from '../../../services/utils';

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
                this.$root.showOverlay();

                settingStore.update(() => {
                    // Re-init the app.
                    this.$root.init();
                }, (error) => {
                    var msg = 'Unknown error.';

                    if (error.status === 422) {
                        msg = utils.parseValidationError(error.data)[0];
                    }

                    this.$root.showOverlay(`Error: ${msg}`, 'error', true);
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
        and (max-device-width : 667px) {
            input[type="text"] {
                width: 100%;
            }
        }
    }
</style>
