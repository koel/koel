import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import type { Events } from '@/config/events'
import Component from './CreatePlaylistContextMenu.vue'

describe('createPlaylistContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async () => {
    h.render(Component)
    eventBus.emit('CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED', { top: 420, left: 42 })
    await h.tick(2)
  }

  it.each<[string, keyof Events]>([
    ['playlist-context-menu-create-simple', 'MODAL_SHOW_CREATE_PLAYLIST_FORM'],
    ['playlist-context-menu-create-smart', 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM'],
    ['playlist-context-menu-create-folder', 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM'],
  ])('when clicking on %s, should emit %s', async (id, eventName) => {
    await renderComponent()
    const emitMock = h.mock(eventBus, 'emit')
    await h.user.click(screen.getByTestId(id))
    await waitFor(() => expect(emitMock).toHaveBeenCalledWith(eventName))
  })
})
