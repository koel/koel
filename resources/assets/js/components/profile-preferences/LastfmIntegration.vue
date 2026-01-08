<template>
  <section>
    <h3 class="text-2xl mb-2">
      <span class="mr-2 text-[var(--lastfm-color)]">
        <Icon :icon="faLastfm" />
      </span>
      {{ t('integrations.lastfm.title') }}
    </h3>

    <div v-if="useLastfm" data-testid="lastfm-integrated">
      <p>
        {{ t('integrations.lastfm.enabled') }}
        {{ t('integrations.lastfm.enabledDescription', { appName }) }}
      </p>
      <p v-if="connected">
        {{ t('integrations.lastfm.accountConnected') }}
      </p>
      <p v-else>{{ t('integrations.lastfm.canConnect') }}</p>
      <p>
        {{ t('integrations.lastfm.connectDescription', { appName }) }}
        <a href="https://www.last.fm/about/trackmymusic" rel="noopener" target="_blank">{{ t('integrations.lastfm.scrobbling') }}</a>.
      </p>
      <div class="buttons mt-4 space-x-2">
        <Btn class="!bg-[var(--lastfm-color)]" @click.prevent="connect">{{ connected ? t('integrations.lastfm.reconnect') : t('integrations.lastfm.connect') }}</Btn>
        <Btn v-if="connected" class="disconnect" gray @click.prevent="disconnect">{{ t('integrations.lastfm.disconnect') }}</Btn>
      </div>
    </div>

    <div v-else data-testid="lastfm-not-integrated">
      <p>
        {{ t('integrations.lastfm.notEnabled') }}
        <span v-if="currentUserCan.manageSettings()" data-testid="lastfm-admin-instruction">
          {{ t('integrations.lastfm.checkDocumentation') }}
          <a href="https://docs.koel.dev/service-integrations#last-fm" target="_blank">{{ t('integrations.lastfm.documentation') }}</a>
          {{ t('integrations.lastfm.forInstructions') }}
        </span>
        <span v-else data-testid="lastfm-user-instruction">
          {{ t('integrations.lastfm.askAdministrator') }}
        </span>
      </p>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { faLastfm } from '@fortawesome/free-brands-svg-icons'
import { computed, defineAsyncComponent } from 'vue'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { useAuthorization } from '@/composables/useAuthorization'
import { useThirdPartyServices } from '@/composables/useThirdPartyServices'
import { forceReloadWindow } from '@/utils/helpers'
import { usePolicies } from '@/composables/usePolicies'
import { useBranding } from '@/composables/useBranding'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))

const { t } = useI18n()
const { currentUser } = useAuthorization()
const { currentUserCan } = usePolicies()
const { useLastfm } = useThirdPartyServices()
const { name: appName } = useBranding()

const connected = computed(() => Boolean(currentUser.value.preferences.lastfm_session_key))

/**
 * Connect the current user to Last.fm.
 * This method opens a new window.
 * Koel will reload once the connection is successful.
 */
const connect = () => window.open(
  `${window.BASE_URL}lastfm/connect?api_token=${authService.getApiToken()}`,
  '_blank',
  'toolbar=no,titlebar=no,location=no,width=1024,height=640',
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
