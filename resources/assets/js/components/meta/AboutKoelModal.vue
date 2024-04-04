<template>
  <div
    v-koel-focus
    class="about text-k-text-secondary text-center max-w-[480px] overflow-hidden relative"
    data-testid="about-koel"
    tabindex="0"
    @keydown.esc="close"
  >
    <main class="p-6">
      <div class="mb-4">
        <img alt="Koel's logo" src="@/../img/logo.svg" width="128" class="inline-block">
      </div>

      <div class="current-version">
        Koel {{ currentVersion }}
        <span v-if="isPlus" class="badge">Plus</span>
        <span v-else>Community</span>
        Edition
        <p v-if="isPlus" class="plus-badge">
          Licensed to {{ license.customerName }} &lt;{{ license.customerEmail }}&gt;
          <br>
          License key: <span class="key font-mono">{{ license.shortKey }}</span>
        </p>

        <template v-else>
          <p v-if="isAdmin" class="py-3">
            <!-- close the modal first to prevent it from overlapping Lemonsqueezy's overlay -->
            <BtnUpgradeToPlus class="!w-auto inline-block !px-6" @click.prevent="showPlusModal" />
          </p>
        </template>
      </div>

      <p v-if="shouldNotifyNewVersion" data-testid="new-version-about">
        <a :href="latestVersionReleaseUrl" target="_blank">
          A new version of Koel is available ({{ latestVersion }})!
        </a>
      </p>

      <p class="author">
        Made with ❤️ by
        <a href="https://github.com/phanan" rel="noopener" target="_blank">Phan An</a>
        and quite a few
        <a href="https://github.com/koel/core/graphs/contributors" rel="noopener" target="_blank">awesome</a>&nbsp;<a
        href="https://github.com/koel/koel/graphs/contributors" rel="noopener" target="_blank"
      >contributors</a>.
      </p>

      <CreditsBlock v-if="isDemo" />
      <SponsorList />

      <p v-if="!isPlus">
        Loving Koel? Please consider supporting its development via
        <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">GitHub Sponsors</a>
        and/or
        <a href="https://opencollective.com/koel" rel="noopener" target="_blank">OpenCollective</a>.
      </p>
    </main>

    <footer>
      <Btn data-testid="close-modal-btn" danger rounded @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { useAuthorization, useKoelPlus, useNewVersionNotification } from '@/composables'
import { eventBus } from '@/utils'

import SponsorList from '@/components/meta/SponsorList.vue'
import Btn from '@/components/ui/form/Btn.vue'
import BtnUpgradeToPlus from '@/components/koel-plus/BtnUpgradeToPlus.vue'
import CreditsBlock from '@/components/meta/CreditsBlock.vue'

const {
  shouldNotifyNewVersion,
  currentVersion,
  latestVersion,
  latestVersionReleaseUrl
} = useNewVersionNotification()

const { isPlus, license } = useKoelPlus()
const { isAdmin } = useAuthorization()

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const showPlusModal = () => {
  close()
  eventBus.emit('MODAL_SHOW_KOEL_PLUS')
}

const isDemo = window.IS_DEMO;
</script>

<style lang="postcss" scoped>
p {
  @apply mx-0 my-3;
}

a {
  @apply text-k-text-primary hover:text-k-accent;
}

.plus-badge {
  .key {
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-image: linear-gradient(97.78deg, #c62be8 17.5%, #671ce4 113.39%);
  }
}
</style>
