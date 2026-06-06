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
        <img alt="Logo" class="inline-block" :src="logo" width="128" />
      </div>

      <div class="current-version">
        {{ appName }} {{ currentVersion }}
        <span v-if="isPlus" class="badge">Plus</span>
        <span v-else>Community</span>
        Edition
        <p v-if="isPlus" class="plus-badge">
          Licensed to {{ license.customerName }} &lt;{{ license.customerEmail }}&gt;
          <br />
          License key: <span class="key font-mono">{{ license.shortKey }}</span>
        </p>

        <template v-else>
          <p v-if="currentUserCan.manageSettings()" class="py-3">
            <!-- close the modal first to prevent it from overlapping Lemonsqueezy's overlay -->
            <BtnUpgradeToPlus class="w-auto! inline-block px-6!" @click.prevent="showPlusModal" />
          </p>
        </template>
      </div>

      <p v-if="shouldNotifyNewVersion" data-testid="new-version-about">
        <a :href="latestVersionReleaseUrl" target="_blank">
          A new version of {{ appName }} is available ({{ latestVersion }})!
        </a>
      </p>

      <p v-if="!hasCustomBranding" class="author">
        Made with ❤️ by
        <a href="https://github.com/phanan" rel="noopener" target="_blank">Phan An</a>
        and quite a few awesome
        <a href="https://github.com/koel/koel/graphs/contributors" rel="noopener" target="_blank">contributors</a>.
      </p>

      <CreditsBlock v-if="isDemo" />

      <p v-if="!isPlus">
        Loving Koel? Please consider supporting its development via
        <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">GitHub Sponsors</a>
        and/or
        <a href="https://opencollective.com/koel" rel="noopener" target="_blank">OpenCollective</a>.
      </p>
    </main>

    <footer>
      <Btn variant="destructive" data-testid="close-modal-btn" rounded @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent } from '@/utils/helpers'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'
import { usePolicies } from '@/composables/usePolicies'
import { useBranding } from '@/composables/useBranding'
import { useModal } from '@/composables/useModal'

import Btn from '@/components/ui/form/Btn.vue'
import BtnUpgradeToPlus from '@/components/koel-plus/BtnUpgradeToPlus.vue'
import CreditsBlock from '@/components/meta/CreditsBlock.vue'

const KoelPlusModal = defineAsyncComponent(() => import('@/components/koel-plus/KoelPlusModal.vue'))

const emit = defineEmits<{ (e: 'close'): void }>()
const { name: appName, logo, hasCustomBranding } = useBranding()
const { shouldNotifyNewVersion, currentVersion, latestVersion, latestVersionReleaseUrl } = useNewVersionNotification()

const { isPlus, license } = useKoelPlus()
const { currentUserCan } = usePolicies()
const { openModal } = useModal()

const close = () => emit('close')

const showPlusModal = () => openModal<'KOEL_PLUS'>(KoelPlusModal)

const isDemo = window.KOEL.is_demo
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
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
