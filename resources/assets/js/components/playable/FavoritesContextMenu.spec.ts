import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { downloadService } from '@/services/downloadService'

const hasOfflineCapablePlayablesMock = vi.fn().mockReturnValue(true)

vi.mock('@/composables/useOfflinePlayback', () => ({
  useOfflinePlayback: () => ({
    swReady: ref(true),
    hasOfflineCapablePlayables: hasOfflineCapablePlayablesMock,
    allPlayablesCached: vi.fn().mockReturnValue(false),
    togglePlayablesOffline: vi.fn(),
  }),
}))

import Component from './FavoritesContextMenu.vue'

describe('favoritesContextMenu.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      commonStore.state.allows_download = true
      commonStore.state.supports_batch_downloading = true
      hasOfflineCapablePlayablesMock.mockReturnValue(true)
    },
  })

  const renderComponent = (playables = h.factory('song').make(3)) => {
    h.render(Component, { props: { playables } })

    return { playables }
  }

  it('downloads the favorites', async () => {
    const downloadMock = h.mock(downloadService, 'fromFavorites')
    hasOfflineCapablePlayablesMock.mockReturnValue(false)
    renderComponent()

    await h.user.click(screen.getByRole('menuitem'))

    expect(downloadMock).toHaveBeenCalled()
  })

  it('hides the download item when downloads are not allowed', () => {
    commonStore.state.allows_download = false
    hasOfflineCapablePlayablesMock.mockReturnValue(false)
    renderComponent()

    expect(screen.queryByRole('menuitem')).toBeNull()
  })

  it('hides the download item for multiple items without batch downloading', () => {
    commonStore.state.supports_batch_downloading = false
    hasOfflineCapablePlayablesMock.mockReturnValue(false)
    renderComponent()

    expect(screen.queryByRole('menuitem')).toBeNull()
  })

  it('shows the offline item for offline-capable favorites', () => {
    commonStore.state.allows_download = false
    const { playables } = renderComponent()

    screen.getByRole('menuitem')
    expect(hasOfflineCapablePlayablesMock).toHaveBeenCalledWith(playables)
  })
})
