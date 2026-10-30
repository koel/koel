<template>
  <slot />
</template>

<script lang="ts" setup>
import { RocketIcon } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { useBranding } from '@/composables/useBranding'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'

import NewReleaseToastContent from '@/components/ui/system-notices/NewReleaseToastContent.vue'

const dismissedVersionStorageKey = 'dismissed-new-release-version'

const { shouldNotifyNewVersion, latestVersion, latestVersionReleaseUrl } = useNewVersionNotification()
const { name: appName } = useBranding()
const { get, set } = useLocalStorage()

const dismissedVersion = ref(get<string>(dismissedVersionStorageKey))
const shown = computed(() => shouldNotifyNewVersion.value && dismissedVersion.value !== latestVersion.value)

const dismiss = (version: string) => {
  set(dismissedVersionStorageKey, version)
  dismissedVersion.value = version
}

onMounted(() => {
  const { toastInfo } = useMessageToaster()

  watch(
    shown,
    show => {
      if (!show) {
        return
      }

      const version = latestVersion.value

      toastInfo({
        content: `${appName} ${version} is available.`,
        icon: RocketIcon,
        component: NewReleaseToastContent,
        props: { appName, version, releaseUrl: latestVersionReleaseUrl.value },
        persistent: true,
        onDismiss: () => dismiss(version),
      })
    },
    { immediate: true },
  )
})
</script>
