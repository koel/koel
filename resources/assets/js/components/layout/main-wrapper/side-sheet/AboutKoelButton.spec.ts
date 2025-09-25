import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './AboutKoelButton.vue'

describe('aboutKoelButton.vue', () => {
  const h = createHarness()

  it('shows no notification when no new available available', () => {
    commonStore.state.current_version = '1.0.0'
    commonStore.state.latest_version = '1.0.0'

    h.actingAsAdmin().render(Component)
    expect(screen.queryByTitle('New version available!')).toBeNull()
    expect(screen.queryByTestId('new-version-indicator')).toBeNull()
    screen.getByTitle('About Koel')
  })

  it('shows notification when new version available', () => {
    commonStore.state.current_version = '1.0.0'
    commonStore.state.latest_version = '1.0.1'

    h.actingAsAdmin().render(Component)
    screen.getByTitle('New version available!')
    screen.getByTestId('new-version-indicator')
  })

  it('doesn\'t show notification to non-admin users', () => {
    commonStore.state.current_version = '1.0.0'
    commonStore.state.latest_version = '1.0.1'

    h.actingAsUser().render(Component)
    expect(screen.queryByTitle('New version available!')).toBeNull()
    expect(screen.queryByTestId('new-version-indicator')).toBeNull()
    screen.getByTitle('About Koel')
  })
})
