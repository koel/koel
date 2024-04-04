<template>
  <section class="text-k-text-secondary">
    <h3 class="text-2xl mb-2">
      <span class="mr-2 text-[var(--lastfm-color)]">
        <Icon :icon="faLastfm" />
      </span>
      Last.fm Integration
    </h3>

    <div v-if="useLastfm" data-testid="lastfm-integrated">
      <p>Last.fm integration is enabled. Koel will attempt to retrieve album and artist information from Last.fm.</p>
      <p v-if="connected">
        It appears that you have connected your Last.fm account as well – Perfect!
      </p>
      <p v-else>You can also connect your Last.fm account here.</p>
      <p>
        Connecting Koel and your Last.fm account enables such exciting features as
        <a href="https://www.last.fm/about/trackmymusic" rel="noopener" target="_blank">scrobbling</a>.
      </p>
      <div class="buttons mt-4 space-x-2">
        <Btn class="!bg-[var(--lastfm-color)]" @click.prevent="connect">{{ connected ? 'Reconnect' : 'Connect' }}</Btn>
        <Btn v-if="connected" class="disconnect" gray @click.prevent="disconnect">Disconnect</Btn>
      </div>
    </div>

    <div v-else data-testid="lastfm-not-integrated">
      <p>
        Last.fm integration is not enabled.
        <span v-if="isAdmin" data-testid="lastfm-admin-instruction">
          Check
          <a href="https://docs.koel.dev/service-integrations#last-fm" class="text-k-highlight" target="_blank">
            Documentation
          </a>
          for instructions.
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
import { authService, http } from '@/services'
import { forceReloadWindow } from '@/utils'
import { useAuthorization, useThirdPartyServices } from '@/composables'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))

const { currentUser, isAdmin } = useAuthorization()
const { useLastfm } = useThirdPartyServices()

const connected = computed(() => Boolean(currentUser.value.preferences!.lastfm_session_key))

/**
 * Connect the current user to Last.fm.
 * This method opens a new window.
 * Koel will reload once the connection is successful.
 */
const connect = () => window.open(
  `${window.BASE_URL}lastfm/connect?api_token=${authService.getApiToken()}`,
  '_blank',
  'toolbar=no,titlebar=no,location=no,width=1024,height=640'
)

const disconnect = async () => {
  await http.delete('lastfm/disconnect')
  forceReloadWindow()
}
</script>

<style lang="postcss" scoped>
section {
  --lastfm-color: #d31f27;
}
</style>
