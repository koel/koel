import { describe, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './MediaBrowserScreen.vue'

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    onRouteChanged: vi.fn(),
    getRouteParam: () => '',
    onScreenActivated: vi.fn(),
    go: vi.fn(),
    url: vi.fn(),
  }),
}))

describe('mediaBrowserScreen.vue', () => {
  const h = createHarness()

  it('shows empty state when library has no songs', () => {
    commonStore.state.song_length = 0

    h.render(Component)
    screen.getByText('No files found.')
  })
})
