<template>
  <FloatingNotice v-if="shown" class="items-start">
    <RocketIcon :size="18" class="shrink-0 mt-0.5 text-k-fg-70" />
    <span class="flex flex-col">
      <span>{{ appName }} {{ latestVersion }} is available.</span>
      <a :href="latestVersionReleaseUrl" rel="noopener" target="_blank">See what's new</a>
    </span>
    <button type="button" title="Dismiss" class="mt-0.5" @click="dismiss">
      <XIcon :size="16" />
    </button>
  </FloatingNotice>
</template>

<script lang="ts" setup>
import { RocketIcon, XIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useBranding } from '@/composables/useBranding'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'

import FloatingNotice from '@/components/ui/FloatingNotice.vue'

const dismissedVersionStorageKey = 'dismissed-new-release-version'

const { shouldNotifyNewVersion, latestVersion, latestVersionReleaseUrl } = useNewVersionNotification()
const { name: appName } = useBranding()
const { get, set } = useLocalStorage()

const dismissedVersion = ref(get<string>(dismissedVersionStorageKey))

const shown = computed(() => shouldNotifyNewVersion.value && dismissedVersion.value !== latestVersion.value)

const dismiss = () => {
  set(dismissedVersionStorageKey, latestVersion.value)
  dismissedVersion.value = latestVersion.value
}
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

a,
button {
  @apply text-k-fg-70 hover:text-k-fg;
}
</style>
