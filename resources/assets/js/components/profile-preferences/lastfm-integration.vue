<template>
  <section class="text-secondary">
    <h1>Last.fm Integration</h1>

    <div v-if="sharedState.useLastfm" data-testid="lastfm-integrated">
      <p>
        This installation of Koel integrates with Last.fm.
        <span v-if="currentUserConnected">
          It appears that you have connected your Last.fm account as well – Perfect!
        </span>
        <span v-else>It appears that you haven’t connected to your Last.fm account though.</span>
      </p>
      <p>
        Connecting Koel and your Last.fm account enables such exciting features as
        <a
          class="text-orange"
          href="https://www.last.fm/about/trackmymusic"
          target="_blank"
          rel="noopener"
        >
          scrobbling
        </a>.
      </p>
      <div class="buttons">
        <btn @click.prevent="connect" class="connect">
          <i class="fa fa-lastfm"></i>
          {{ currentUserConnected ? 'Reconnect' : 'Connect' }}
        </btn>

        <btn v-if="currentUserConnected" @click.prevent="disconnect" class="disconnect" gray>Disconnect</btn>
      </div>
    </div>

    <div v-else data-testid="lastfm-not-integrated">
      <p>
        This installation of Koel has no Last.fm integration.
        <span v-if="userState.current.is_admin" data-testid="lastfm-admin-instruction">
          Visit
          <a href="https://docs.koel.dev/3rd-party.html#last-fm" class="text-orange" target="_blank">Koel’s Wiki</a>
          for a quick how-to.
        </span>
        <span v-else data-testid="lastfm-user-instruction">
          Try politely asking an administrator to enable it.
        </span>
      </p>
    </div>
  </section>
</template>

<script lang="ts">
import Vue from 'vue'
import { sharedStore, userStore } from '@/stores'
import { auth, http } from '@/services'
import { forceReloadWindow } from '@/utils'

export default Vue.extend({
  components: {
    Btn: () => import('@/components/ui/btn.vue')
  },

  data: () => ({
    userState: userStore.state,
    sharedState: sharedStore.state
  }),

  computed: {
    currentUserConnected (): boolean {
      return this.userState?.current.preferences.lastfm_session_key
    }
  },

  methods: {
    /**
     * Connect the current user to Last.fm.
     * This method opens a new window.
     * Koel will reload once the connection is successful.
     */
    connect: (): void => {
      window.open(
        `${window.BASE_URL}lastfm/connect?api_token=${auth.getToken()}`,
        '_blank',
        'toolbar=no,titlebar=no,location=no,width=1024,height=640'
      )
    },

    /**
     * Disconnect the current user from Last.fm.
     */
    disconnect: (): void => {
      http.delete('lastfm/disconnect').then(forceReloadWindow)
    }
  }
})
</script>

<style lang="scss" scoped>
.buttons {
  margin-top: 1.25rem;

  .connect {
    background: #d31f27; // Last.fm color yo!
  }
}
</style>
