<template>
  <div
    v-koel-focus
    class="about text-center max-w-[480px] overflow-hidden relative"
    data-testid="about-koel"
    tabindex="0"
    @keydown.esc="close"
  >
    <main class="p-6">
      <div class="mb-4">
        <img alt="Logo" class="inline-block" :src="logo" width="128">
      </div>

      <div class="current-version">
        {{ appName }} {{ currentVersion }}
        <span v-if="isPlus" class="badge">{{ t('meta.plus') }}</span>
        <span v-else>{{ t('meta.community') }}</span>
        {{ t('meta.edition') }}
        <p v-if="isPlus" class="plus-badge">
          {{ t('meta.licensedTo', { name: license.customerName, email: license.customerEmail }) }}
          <br>
          {{ t('meta.licenseKey') }} <span class="key font-mono">{{ license.shortKey }}</span>
        </p>

        <template v-else>
          <p v-if="currentUserCan.manageSettings()" class="py-3">
            <!-- close the modal first to prevent it from overlapping Lemonsqueezy's overlay -->
            <BtnUpgradeToPlus class="!w-auto inline-block !px-6" @click.prevent="showPlusModal" />
          </p>
        </template>
      </div>

      <p v-if="shouldNotifyNewVersion" data-testid="new-version-about">
        <a :href="latestVersionReleaseUrl" target="_blank">
          {{ t('meta.newVersion', { app: appName, version: latestVersion }) }}
        </a>
      </p>

      <p v-if="!hasCustomBranding" class="author">
        {{ t('meta.madeWith') }}
        <a href="https://github.com/phanan" rel="noopener" target="_blank">Phan An</a>
        {{ t('meta.andQuiteAFewAwesome') }}
        <a href="https://github.com/koel/koel/graphs/contributors" rel="noopener" target="_blank">{{ t('meta.contributors') }}</a>.
      </p>

      <CreditsBlock v-if="isDemo" />

      <p v-if="!isPlus">
        {{ t('meta.supportDevelopment') }}
        <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">{{ t('meta.gitHubSponsors') }}</a>
        {{ t('meta.and') }}
        <a href="https://opencollective.com/koel" rel="noopener" target="_blank">{{ t('meta.openCollective') }}</a>.
      </p>
    </main>

    <footer>
      <Btn danger data-testid="close-modal-btn" rounded @click.prevent="close">{{ t('playlists.close') }}</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { useI18n } from 'vue-i18n'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'
import { eventBus } from '@/utils/eventBus'
import { usePolicies } from '@/composables/usePolicies'
import { useBranding } from '@/composables/useBranding'

import Btn from '@/components/ui/form/Btn.vue'
import BtnUpgradeToPlus from '@/components/koel-plus/BtnUpgradeToPlus.vue'
import CreditsBlock from '@/components/meta/CreditsBlock.vue'

const emit = defineEmits<{ (e: 'close'): void }>()
const { t } = useI18n()
const { name: appName, logo, hasCustomBranding } = useBranding()
const {
  shouldNotifyNewVersion,
  currentVersion,
  latestVersion,
  latestVersionReleaseUrl,
} = useNewVersionNotification()

const { isPlus, license } = useKoelPlus()
const { currentUserCan } = usePolicies()

const close = () => emit('close')

const showPlusModal = () => {
  close()
  eventBus.emit('MODAL_SHOW_KOEL_PLUS')
}

const isDemo = window.IS_DEMO
</script>

<style lang="postcss" scoped>
p {
  @apply mx-0 my-3;
}

a {
  @apply text-k-fg hover:text-k-highlight;
}

.plus-badge {
  .key {
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-image: linear-gradient(97.78deg, #c62be8 17.5%, #671ce4 113.39%);
  }
}
</style>
