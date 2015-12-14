<template>
    <div id="profileWrapper">
        <h1 class="heading">
            <span>Profile &amp; Preferences</span>
        </h1>
    
        <div class="main-scroll-wrap">
            <form @submit.prevent="update">
                <div class="form-row">
                    <label for="inputProfileName">Name</label>
                    <input type="text" id="inputProfileName" v-model="state.current.name">
                </div>

                <div class="form-row">
                    <label for="inputProfileEmail">Email Address</label>
                    <input type="email" id="inputProfileEmail" v-model="state.current.email">
                </div>

                <div class="change-pwd">
                    <div class="form-row">
                        <p class="help">If you want to change your password, enter it below. <br>
                            Otherwise, just leave the next two fields empty. It’s OK – no one will blame you.</p>
                    </div>

                    <div class="form-row">
                        <label for="inputProfilePassword">New Password</label>
                        <input v-model="pwd" type="password" id="inputProfilePassword" autocomplete="off">
                    </div>

                    <div class="form-row">
                        <label for="inputProfileConfirmPassword">Confirm Password</label>
                        <input v-model="confirmPwd" type="password" id="inputProfileConfirmPassword" autocomplete="off">
                    </div>
                </div>

                <div class="form-row">
                    <button type="submit">Save</button>
                    <span v-show="showStatus" class="status">Saved!</span>
                </div>
            </form>

            <div class="preferences">
                <div class="form-row">
                    <input type="checkbox" v-model="prefs.notify" @change="savePreference()">
                    <span @click="prefs.notify = !prefs.notify">Show “Now Playing” song notification</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import $ from 'jquery';
    
    import userStore from '../../../stores/user';
    import preferenceStore from '../../../stores/preference';

    export default {
        data() {
            return {
                state: userStore.state,
                cache: userStore.stub,
                pwd: '',
                confirmPwd: '',
                showStatus: false,
                prefs: preferenceStore.state,
            };
        },

        methods: {
            /**
             * Update the current user's profile.
             */
            update() {
                // A little validation put in a small place.
                if ((this.pwd || this.confirmPwd) && this.pwd !== this.confirmPwd) {
                    $('#inputProfilePassword, #inputProfileConfirmPassword').addClass('error');

                    return;
                }

                $('#inputProfilePassword, #inputProfileConfirmPassword').removeClass('error');

                userStore.updateProfile(this.pwd, () => {
                    this.pwd = '';
                    this.confirmPwd = '';

                    // "Save!" aaaaaaaand it's gone!
                    this.showStatus = true;
                    setTimeout(() => this.showStatus = false, 3000)
                });
            },

            /**
             * Save the current user's preference. 
             * Right now it's only "Song notification."
             */
            savePreference() {
                preferenceStore.save();
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #profileWrapper {
        input {
            &[type="text"], &[type="email"], &[type="password"] {
                width: 192px;    
            }

            &.error {
                // Chrome won't give up its autofill style, so this is kind of a hack.
                box-shadow: 0 0 0px 1000px #ff867a inset;
            }
        }

        .change-pwd {
            margin-top: 24px;
        }

        .status {
            margin-left: 8px;
            color: $colorGreen;
        }

        .preferences {
            margin-top: 32px;
            border-top: 1px solid $color2ndBgr;
        }

        

        @media only screen 
        and (max-device-width : 667px) 
        and (orientation : portrait) {
            input {
                &[type="text"], &[type="email"], &[type="password"] {
                    width: 100%;
                    height: 32px;
                }
            }
        }
    }
</style>
