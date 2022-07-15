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
          class="text-highlight"
          href="https://www.last.fm/about/trackmymusic"
          rel="noopener"
          target="_blank"
        >
          scrobbling
        </a>.
      </p>
      <div class="buttons">
        <Btn class="connect" @click.prevent="connect">
          <icon :icon="faLastfm"/>
          {{ connected ? 'Reconnect' : 'Connect' }}
        </Btn>

        <Btn v-if="connected" class="disconnect" gray @click.prevent="disconnect">Disconnect</Btn>
      </div>
    </div>

    <div v-else data-testid="lastfm-not-integrated">
      <p>
        This installation of Koel has no Last.fm integration.
        <span v-if="isAdmin" data-testid="lastfm-admin-instruction">
          Visit
          <a href="https://docs.koel.dev/3rd-party.html#last-fm" class="text-highlight" target="_blank">Koel’s Wiki</a>
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
import { faLastfm } from '@fortawesome/free-brands-svg-icons'
import { computed, defineAsyncComponent } from 'vue'
import { authService, httpService } from '@/services'
import { forceReloadWindow } from '@/utils'
import { useAuthorization, useThirdPartyServices } from '@/composables'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const { currentUser, isAdmin } = useAuthorization()
const { useLastfm } = useThirdPartyServices()

const connected = computed(() => Boolean(currentUser.value.preferences!.lastfm_session_key))

/**
 * Connect the current user to Last.fm.
 * This method opens a new window.
 * Koel will reload once the connection is successful.
 */
const connect = () => window.open(
  `${window.BASE_URL}lastfm/connect?api_token=${authService.getToken()}`,
  '_blank',
  'toolbar=no,titlebar=no,location=no,width=1024,height=640'
)

const disconnect = async () => {
  await httpService.delete('lastfm/disconnect')
  forceReloadWindow()
}
</script>

<style lang="scss" scoped>
.buttons {
  margin-top: 1.25rem;

  > * + * {
    margin-left: 0.5rem;
  }

  .connect {
    background: #d31f27; // Last.fm color yo!
  }
}
</style>
