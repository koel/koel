<template>
  <div
    v-if="shown"
    class="bg-k-bg text-[0.9rem] px-6 py-4 flex z-10 space-x-3"
    data-testid="support-bar"
  >
    <p class="flex-1" v-html="t('content.support.description')" />
    <button type="button" @click.prevent="close">{{ t('content.support.hide') }}</button>
    <span class="block after:content-['•'] after:block" />
    <button type="button" @click.prevent="stopBugging">{{ t('content.support.dontBugAgain') }}</button>
  </div>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import { ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { preferenceStore } from '@/stores/preferenceStore'
import { useKoelPlus } from '@/composables/useKoelPlus'

const { t } = useI18n()

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
  if (!initialized) {
    return
  }

  if (preferenceStore.state.support_bar_no_bugging || isMobile.any) {
    return
  }

  if (isPlus.value) {
    return
  }

  setUpShowBarTimeout()
}, { immediate: true })
</script>

<style lang="postcss" scoped>
a {
  @apply text-k-fg hover:text-k-highlight;
}

button {
  @apply text-k-fg text-[0.9rem] hover:text-k-highlight;
}
</style>
