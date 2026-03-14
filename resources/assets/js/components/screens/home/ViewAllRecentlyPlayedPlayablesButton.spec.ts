import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ViewAllRecentlyPlayedPlayablesButton.vue'

const goMock = vi.fn()

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    go: goMock,
    url: (name: string) => `/#/${name}`,
  }),
}))

describe('viewAllRecentlyPlayedPlayablesButton.vue', () => {
  const h = createHarness()

  it('navigates to recently played screen on click', async () => {
    h.render(Component)
    await h.user.click(screen.getByText('View All'))

    expect(goMock).toHaveBeenCalledWith('/#/recently-played')
  })
})
