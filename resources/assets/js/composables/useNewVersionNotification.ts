import { useAuthorization } from '@/composables/useAuthorization'
import compareVersions from 'compare-versions'
import { commonStore } from '@/stores'
import { computed, toRef } from 'vue'

export const useNewVersionNotification = () => {
  const { isAdmin } = useAuthorization()

  const latestVersion = toRef(commonStore.state, 'latestVersion')
  const currentVersion = toRef(commonStore.state, 'currentVersion')

  const hasNewVersion = computed(() => compareVersions.compare(latestVersion.value, currentVersion.value, '>'))
  const shouldNotifyNewVersion = computed(() => isAdmin.value && hasNewVersion.value)

  const latestVersionReleaseUrl = computed(() => {
    return `https://github.com/koel/koel/releases/tag/${latestVersion.value}`
  })

  return {
    shouldNotifyNewVersion,
    currentVersion,
    latestVersion,
    latestVersionReleaseUrl
  }
}
