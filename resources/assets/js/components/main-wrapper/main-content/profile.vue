<template>
  <section id="profileWrapper">
    <h1 class="heading">
      <span>Profile &amp; Preferences</span>
    </h1>

    <div class="main-scroll-wrap">
      <form @submit.prevent="update">
        <div class="form-row">
          <label for="inputProfileName">Name</label>
          <input type="text" name="name" id="inputProfileName" v-model="state.current.name">
        </div>

        <div class="form-row">
          <label for="inputProfileEmail">Email Address</label>
          <input type="email" name="email" id="inputProfileEmail" v-model="state.current.email">
        </div>

        <div class="change-pwd">
          <div class="form-row">
            <p class="help">If you want to change your password, enter it below. <br>
              Otherwise, just leave the next two fields empty. It’s OK – no one will judge you.</p>
          </div>

          <div class="form-row">
            <label for="inputProfilePassword">New Password</label>
            <input v-model="pwd" name="password" type="password" id="inputProfilePassword" autocomplete="off">
          </div>

          <div class="form-row">
            <label for="inputProfileConfirmPassword">Confirm Password</label>
            <input v-model="confirmPwd" name="confirmPassword" type="password" id="inputProfileConfirmPassword" autocomplete="off">
          </div>
        </div>

        <div class="form-row">
          <button type="submit" class="btn btn-submit">Save</button>
        </div>
      </form>

      <div class="preferences">
        <div class="form-row">
          <label>
            <input type="checkbox" name="notify" v-model="prefs.notify" @change="savePreference">
            Show “Now Playing” song notification
          </label>
        </div>
        <div class="form-row">
          <label>
            <input type="checkbox" name="confirmClosing" v-model="prefs.confirmClosing" @change="savePreference">
            Confirm before closing Koel
          </label>
        </div>
        <div class="form-row">
          <label>
            <input type="checkbox" name="transcodeOnMobile" v-model="prefs.transcodeOnMobile" @change="savePreference">
            Convert and play media at 128kbps on mobile
          </label>
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
              It appears that you haven’t connected to your Last.fm account though.
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
              <a href="https://koel.phanan.net/docs/#/3rd-party?id=last-fm" target="_blank">Koel’s Wiki</a>
              for a quick how-to.
            </span>
            <span v-else>Try politely asking your administrator to enable it.</span>
          </p>
        </div>
      </section>
    </div>
  </section>
</template>

<script>
import { each } from 'lodash'
import { userStore, preferenceStore, sharedStore } from '@/stores'
import { forceReloadWindow, $ } from '@/utils'
import { http, ls } from '@/services'

export default {
  name: 'main-wrapper--main-content--profile',

  data () {
    return {
      state: userStore.state,
      cache: userStore.stub,
      pwd: '',
      confirmPwd: '',
      prefs: preferenceStore.state,
      sharedState: sharedStore.state
    }
  },

  methods: {
    /**
     * Update the current user's profile.
     */
    async update () {
      const passwordFields = Array.from(
        document.querySelectorAll('#inputProfilePassword, #inputProfileConfirmPassword')
      )
      // A little validation put in a small place.
      if ((this.pwd || this.confirmPwd) && this.pwd !== this.confirmPwd) {
        each(passwordFields, el => $.addClass(el, 'error'))
        return
      }

      each(passwordFields, el => $.removeClass(el, 'error'))

      await userStore.updateProfile(this.pwd)
      this.pwd = ''
      this.confirmPwd = ''
    },

    /**
     * Save the current user's preference.
     */
    savePreference () {
      this.$nextTick(() => preferenceStore.save())
    },

    /**
     * Connect the current user to Last.fm.
     * This method opens a new window.
     * Koel will reload once the connection is successful.
     */
    connectToLastfm () {
      window.open(
        `${window.BASE_URL}api/lastfm/connect?jwt-token=${ls.get('jwt-token')}`,
        '_blank',
        'toolbar=no,titlebar=no,location=no,width=1024,height=640'
      )
    },

    /**
     * Disconnect the current user from Last.fm.
     * Oh God why.
     */
    disconnectFromLastfm () {
      // Should we use userStore?
      // - We shouldn't. This doesn't have anything to do with stores.
      // Should we confirm the user?
      // - Nope. Users should be grown-ass adults who take responsibilty of their actions.
      // But one of my users is my new born kid!
      // - Then? Kids will fuck things up anyway.
      http.delete('lastfm/disconnect', {}, forceReloadWindow)
    }
  }
}
</script>

<style lang="scss">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

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

    label {
      font-size: $fontSize;
    }
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

  @media only screen and (max-width : 667px) {
    input {
      &[type="text"], &[type="email"], &[type="password"] {
        width: 100%;
        height: 32px;
      }
    }
  }
}
</style>
