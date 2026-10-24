import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { nextTick } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './NewReleaseNotification.vue'

describe('newReleaseNotification', () => {
  const h = createHarness({
    beforeEach: () => {
      localStorage.clear()
      commonStore.state.current_version = 'v1.0.0'
      commonStore.state.latest_version = 'v1.1.0'
    },
  })

  it('stays dismissed for the dismissed version', async () => {
    h.actingAsAdmin().render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Dismiss' }))
    expect(screen.queryByRole('link')).toBeNull()

    h.render(Component)
    expect(screen.queryByRole('link')).toBeNull()
  })

  it('shows again for a newer version than the dismissed one', async () => {
    h.actingAsAdmin().render(Component)
    await h.user.click(screen.getByRole('button', { name: 'Dismiss' }))

    commonStore.state.latest_version = 'v1.2.0'
    await nextTick()

    expect(screen.getByRole('link').getAttribute('href')).toBe('https://github.com/koel/koel/releases/tag/v1.2.0')
  })
})
