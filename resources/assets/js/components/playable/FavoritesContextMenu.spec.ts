import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { ref } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenModal } from '@/__tests__/assertions'
import { commonStore } from '@/stores/commonStore'
import { downloadService } from '@/services/downloadService'
import CreateEmbedForm from '@/components/embed/CreateEmbedForm.vue'

const openModalMock = vi.fn()

vi.mock('@/composables/useModal', () => ({
  useModal: () => ({
    openModal: openModalMock,
  }),
}))

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
      commonStore.state.allows_embedding = true
      hasOfflineCapablePlayablesMock.mockReturnValue(true)
      openModalMock.mockClear()
    },
  })

  const renderComponent = (playables = h.factory('song').make(3)) => {
    h.render(Component, { props: { playables } })

    return { playables }
  }

  it('downloads the favorites', async () => {
    const downloadMock = h.mock(downloadService, 'fromFavorites')
    renderComponent()

    await h.user.click(screen.getByRole('menuitem', { name: 'Download' }))

    expect(downloadMock).toHaveBeenCalled()
  })

  it('hides the download item when downloads are not allowed', () => {
    commonStore.state.allows_download = false
    renderComponent()

    expect(screen.queryByRole('menuitem', { name: 'Download' })).toBeNull()
  })

  it('hides the download item for multiple items without batch downloading', () => {
    commonStore.state.supports_batch_downloading = false
    renderComponent()

    expect(screen.queryByRole('menuitem', { name: 'Download' })).toBeNull()
  })

  it('shows the offline item for offline-capable favorites', () => {
    const { playables } = renderComponent()

    screen.getByRole('menuitem', { name: 'Make Available Offline' })
    expect(hasOfflineCapablePlayablesMock).toHaveBeenCalledWith(playables)
  })

  it('requests the embed form for the current user favorites', async () => {
    const user = h.factory('user').state('current').make() as CurrentUser
    h.actingAsUser(user)
    renderComponent()

    await h.user.click(screen.getByRole('menuitem', { name: 'Embed…' }))

    await assertOpenModal(openModalMock, CreateEmbedForm, {
      embeddable: { type: 'favorites', owner_name: user.name },
    })
  })

  it('hides the embed item when embedding is disabled', () => {
    commonStore.state.allows_embedding = false
    renderComponent()

    expect(screen.queryByRole('menuitem', { name: 'Embed…' })).toBeNull()
  })

  it('hides the embed item for guests', () => {
    h.actingAsUser(h.factory('user').make({ role: 'guest' }) as CurrentUser)
    renderComponent()

    expect(screen.queryByRole('menuitem', { name: 'Embed…' })).toBeNull()
  })
})
