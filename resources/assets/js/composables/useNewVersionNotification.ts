import compareVersions from 'compare-versions'
import { computed, toRef } from 'vue'
import { commonStore } from '@/stores'
import { useAuthorization } from '@/composables/useAuthorization'

export const useNewVersionNotification = () => {
  const { isAdmin } = useAuthorization()

  const latestVersion = toRef(commonStore.state, 'latest_version')
  const currentVersion = toRef(commonStore.state, 'current_version')

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
