<template>
  <div
    v-if="shown"
    class="bg-k-bg-primary text-[0.9rem] px-6 py-4 flex text-k-text-secondary z-10 space-x-3"
    data-testid="support-bar"
  >
    <p class="flex-1">
      Loving Koel? Please consider supporting its development via
      <a href="https://github.com/users/phanan/sponsorship" rel="noopener" target="_blank">GitHub Sponsors</a>
      and/or
      <a href="https://opencollective.com/koel" rel="noopener" target="_blank">OpenCollective</a>.
    </p>
    <button type="button" @click.prevent="close">Hide</button>
    <span class="block after:content-['•'] after:block" />
    <button type="button" @click.prevent="stopBugging">
      Don't bug me again
    </button>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { ref, watch } from 'vue'
import { preferenceStore } from '@/stores'
import { useKoelPlus } from '@/composables'

const delayUntilShow = 30 * 60 * 1000 // 30 minutes

const shown = ref(false)

const { isPlus } = useKoelPlus()
const setUpShowBarTimeout = () => setTimeout(() => (shown.value = true), delayUntilShow)
const close = () => shown.value = false

const stopBugging = () => {
  preferenceStore.set('support_bar_no_bugging', true)
  close()
}

watch(preferenceStore.initialized, initialized => {
  if (!initialized) return
  if (preferenceStore.state.support_bar_no_bugging || isMobile.any) return
  if (isPlus.value) return

  setUpShowBarTimeout()
}, { immediate: true })
</script>

<style lang="postcss" scoped>
a {
  @apply text-k-text-primary hover:text-k-accent;
}

button {
  @apply text-k-text-primary text-[0.9rem] hover:text-k-accent;
}
</style>
