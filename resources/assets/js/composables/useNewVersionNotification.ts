import compareVersions from 'compare-versions'
import { computed, toRef } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { usePolicies } from '@/composables/usePolicies'

export const useNewVersionNotification = () => {
  const { currentUserCan } = usePolicies()

  const latestVersion = toRef(commonStore.state, 'latest_version')
  const currentVersion = toRef(commonStore.state, 'current_version')

  const hasNewVersion = computed(() => compareVersions.compare(latestVersion.value, currentVersion.value, '>'))
  const shouldNotifyNewVersion = computed(() => currentUserCan.manageSettings() && hasNewVersion.value)

  const latestVersionReleaseUrl = computed(() => {
    return `https://github.com/koel/koel/releases/tag/${latestVersion.value}`
  })

  return {
    shouldNotifyNewVersion,
    currentVersion,
    latestVersion,
    latestVersionReleaseUrl,
  }
}
