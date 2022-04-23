<template>
  <section class="text-secondary">
    <h1>Last.fm Integration</h1>

    <div v-if="useLastfm" data-testid="lastfm-integrated">
      <p>
        This installation of Koel integrates with Last.fm.
        <span v-if="connected">
          It appears that you have connected your Last.fm account as well – Perfect!
        </span>
        <span v-else>It appears that you haven’t connected to your Last.fm account though.</span>
      </p>
      <p>
        Connecting Koel and your Last.fm account enables such exciting features as
        <a
          class="text-orange"
          href="https://www.last.fm/about/trackmymusic"
          rel="noopener"
          target="_blank"
        >
          scrobbling
        </a>.
      </p>
      <div class="buttons">
        <Btn class="connect" @click.prevent="connect">
          <i class="fa fa-lastfm"></i>
          {{ connected ? 'Reconnect' : 'Connect' }}
        </Btn>

        <Btn v-if="connected" class="disconnect" gray @click.prevent="disconnect">Disconnect</Btn>
      </div>
    </div>

    <div v-else data-testid="lastfm-not-integrated">
      <p>
        This installation of Koel has no Last.fm integration.
        <span v-if="currentUser.is_admin" data-testid="lastfm-admin-instruction">
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

<script lang="ts" setup>
import { computed, defineAsyncComponent, toRef } from 'vue'
import { sharedStore, userStore } from '@/stores'
import { auth, http } from '@/services'
import { forceReloadWindow } from '@/utils'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const currentUser = toRef(userStore.state, 'current')
const useLastfm = toRef(sharedStore.state, 'useLastfm')

const connected = computed(() => Boolean(currentUser.value.preferences.lastfm_session_key))

/**
 * Connect the current user to Last.fm.
 * This method opens a new window.
 * Koel will reload once the connection is successful.
 */
const connect = () => window.open(
  `${window.BASE_URL}lastfm/connect?api_token=${auth.getToken()}`,
  '_blank',
  'toolbar=no,titlebar=no,location=no,width=1024,height=640'
)

const disconnect = async () => {
  await http.delete('lastfm/disconnect')
  forceReloadWindow()
}
</script>

<style lang="scss" scoped>
.buttons {
  margin-top: 1.25rem;

  .connect {
    background: #d31f27; // Last.fm color yo!
  }
}
</style>