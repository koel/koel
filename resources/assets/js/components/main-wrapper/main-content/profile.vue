<template>
    <section id="profileWrapper">
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

            <section class="lastfm" >
                <h1>Last.fm Integration</h1>

                <div v-if="sharedState.useLastfm">
                    <p>This installation of Koel integrates with Last.fm.
                        <span v-if="state.current.preferences.lastfm_session_key">
                            It appears that you have connected your Last.fm account as well – Perfect!
                        </span>
                        <span v-else>
                            It appears that you haven’t connected to your Last.fm account thought.
                        </span>
                    </p>
                    <p>
                        Connecting Koel and your Last.fm account enables exciting features – scrobbling is one of them.
                    </p>
                    <p v-if="state.current.preferences.lastfm_session_key">
                        For the sake of democracy, you have the option to disconnect from Last.fm too. 
                        Doing so will reload Koel, though.
                    </p>


                    <div class="buttons">
                        <button @click.prevent="connectToLastfm" class="connect">
                            <i class="fa fa-lastfm"></i>
                            {{ state.current.preferences.lastfm_session_key ? 'Reconnect' : 'Connect' }}
                        </button>

                        <button 
                            v-if="state.current.preferences.lastfm_session_key" 
                            @click.prevent="disconnectFromLastfm" 
                            class="disconnect"
                        >
                            Disconnect
                        </button>
                    </div>        
                </div>

                <div v-else>
                    <p>This installation of Koel has no Last.fm integration.
                        <span v-if="state.current.is_admin">Visit 
                            <a href="https://github.com/phanan/koel/wiki" target="_blank">Koel’s Wiki</a>
                            for a quick how-to. Really, you should do it.
                        </span>
                        <span v-else>Try politely asking your adminstrator to enable it.</span>
                    </p>
                </div>
            </section>
        </div>
    </section>
</template>

<script>
    import $ from 'jquery';
    
    import userStore from '../../../stores/user';
    import preferenceStore from '../../../stores/preference';
    import sharedStore from '../../../stores/shared';
    import http from '../../../services/http';
    import ls from '../../../services/ls';

    export default {
        data() {
            return {
                state: userStore.state,
                cache: userStore.stub,
                pwd: '',
                confirmPwd: '',
                showStatus: false,
                prefs: preferenceStore.state,
                sharedState: sharedStore.state,
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

            /**
             * Connect the current user to Last.fm.
             * This method opens a new window.
             * Koel will reload once the connection is successful.
             */
            connectToLastfm() {
                window.open(
                    `/api/lastfm/connect?jwt-token=${ls.get('jwt-token')}`, 
                    '_blank', 
                    'toolbar=no,titlebar=no,location=no,width=1024,height=640'
                );
            },

            /**
             * Disconnect the current user from Last.fm.
             * Oh God why.
             */
            disconnectFromLastfm() {
                // Should we use userStore?
                // - We shouldn't. This doesn't have anything to do with stores.
                // Should we confirm the user?
                // - Nope. Users should be grown-ass adults who take responsibilty of their actions.
                // But one of my users is my new born kid!
                // - Then? Kids will fuck things up anyway.
                http.delete('lastfm/disconnect', {}, () => window.location.reload());
            }
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

        .lastfm {
            border-top: 1px solid $color2ndBgr;
            color: $color2ndText;
            margin-top: 16px;
            padding-top: 16px;

            a {
                color: $colorHighlight;
            }

            h1 {
                font-size: 24px;
                margin-bottom: 16px;
            }

            .buttons {
                margin-top: 16px;

                .connect {
                    background: #d31f27; // Last.fm color yo!
                }

                .disconnect {
                    background: $colorGrey; // Our color yo!
                }
            }
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
