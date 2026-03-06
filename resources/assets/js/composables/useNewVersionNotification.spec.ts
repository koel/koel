import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'

const mockManageSettings = vi.fn()

vi.mock('@/stores/commonStore', () => ({
  commonStore: {
    state: {
      latest_version: '7.0.0',
      current_version: '6.5.0',
    },
  },
}))

vi.mock('@/composables/usePolicies', () => ({
  usePolicies: () => ({
    currentUserCan: {
      manageSettings: mockManageSettings,
    },
  }),
}))

import { commonStore } from '@/stores/commonStore'
import { useNewVersionNotification } from './useNewVersionNotification'

describe('useNewVersionNotification', () => {
  createHarness({
    beforeEach: () => {
      commonStore.state.latest_version = '7.0.0'
      commonStore.state.current_version = '6.5.0'
      mockManageSettings.mockReturnValue(true)
    },
  })

  it('detects new version available', () => {
    const { shouldNotifyNewVersion } = useNewVersionNotification()
    expect(shouldNotifyNewVersion.value).toBe(true)
  })

  it('does not notify when versions are equal', () => {
    commonStore.state.latest_version = '6.5.0'
    const { shouldNotifyNewVersion } = useNewVersionNotification()
    expect(shouldNotifyNewVersion.value).toBe(false)
  })

  it('does not notify when current is newer', () => {
    commonStore.state.current_version = '8.0.0'
    const { shouldNotifyNewVersion } = useNewVersionNotification()
    expect(shouldNotifyNewVersion.value).toBe(false)
  })

  it('does not notify when user cannot manage settings', () => {
    mockManageSettings.mockReturnValue(false)
    const { shouldNotifyNewVersion } = useNewVersionNotification()
    expect(shouldNotifyNewVersion.value).toBe(false)
  })

  it('exposes version refs', () => {
    const { currentVersion, latestVersion } = useNewVersionNotification()
    expect(currentVersion.value).toBe('6.5.0')
    expect(latestVersion.value).toBe('7.0.0')
  })

  it('builds release URL from latest version', () => {
    const { latestVersionReleaseUrl } = useNewVersionNotification()
    expect(latestVersionReleaseUrl.value).toBe('https://github.com/koel/koel/releases/tag/7.0.0')
  })
})
