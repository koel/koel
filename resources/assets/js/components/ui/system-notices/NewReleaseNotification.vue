<template>
  <SystemFloatingNotice v-if="shown" class="items-start" dismissible @dismiss="dismiss">
    <template #icon>
      <RocketIcon :size="18" class="mt-0.5 text-k-fg-70" />
    </template>
    <span class="flex flex-col">
      <span>{{ appName }} {{ latestVersion }} is available.</span>
      <a :href="latestVersionReleaseUrl" class="text-k-fg-70 hover:text-k-fg" rel="noopener" target="_blank"
        >See what's new</a
      >
    </span>
  </SystemFloatingNotice>
</template>

<script lang="ts" setup>
import { RocketIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { useBranding } from '@/composables/useBranding'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'

import SystemFloatingNotice from '@/components/ui/system-notices/SystemFloatingNotice.vue'

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
