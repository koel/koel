import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import type { Events } from '@/config/events'
import Component from './CreatePlaylistContextMenu.vue'

describe('createPlaylistContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async () => h.render(Component)

  it.each<[string, keyof Events]>([
    ['New Playlist…', 'MODAL_SHOW_CREATE_PLAYLIST_FORM'],
    ['New Smart Playlist…', 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM'],
    ['New Folder…', 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM'],
  ])('when clicking on %s, should emit %s', async (id, eventName) => {
    await renderComponent()
    const emitMock = h.mock(eventBus, 'emit')
    await h.user.click(screen.getByText(id))
    await waitFor(() => expect(emitMock).toHaveBeenCalledWith(eventName))
  })
})
