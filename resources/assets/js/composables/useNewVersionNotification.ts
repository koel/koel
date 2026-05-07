import { computed, toRef } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { usePolicies } from '@/composables/usePolicies'

const parseVersion = (version: string) => {
  const cleaned = version.replace(/^v/, '').split('+', 1)[0]
  const [base, prerelease] = cleaned.split('-', 2)
  const segments = base.split('.').map(part => Number.parseInt(part, 10) || 0)

  if (!prerelease) {
    return segments
  }

  // Sentinel below zero so a pre-release sorts lower than its base release (semver §11).
  return [...segments, -1, ...prerelease.split('.').map(part => Number.parseInt(part, 10) || 0)]
}

const isNewerVersion = (candidate: string, current: string) => {
  const left = parseVersion(candidate)
  const right = parseVersion(current)
  const length = Math.max(left.length, right.length)

  for (let i = 0; i < length; i++) {
    const leftPart = left[i] ?? 0
    const rightPart = right[i] ?? 0

    if (leftPart !== rightPart) {
      return leftPart > rightPart
    }
  }

  return false
}

export const useNewVersionNotification = () => {
  const { currentUserCan } = usePolicies()

  const latestVersion = toRef(commonStore.state, 'latest_version')
  const currentVersion = toRef(commonStore.state, 'current_version')

  const hasNewVersion = computed(() => isNewerVersion(latestVersion.value, currentVersion.value))
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
