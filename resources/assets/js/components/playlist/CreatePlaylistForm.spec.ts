import { describe, expect, it } from 'vitest'
import { ref } from 'vue'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistStore } from '@/stores/playlistStore'
import { ModalContextKey } from '@/symbols'
import Component from './CreatePlaylistForm.vue'

describe('createPlaylistForm.vue', () => {
  const h = createHarness()

  it('creates playlist with no playables', async () => {
    const folder = h.factory('playlist-folder')
    const storeMock = h.mock(playlistStore, 'store').mockResolvedValue(h.factory('playlist'))

    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: [ref({ folder })],
        },
      },
    })

    expect(screen.queryByTestId('from-playables')).toBeNull()

    await h.type(screen.getByPlaceholderText('Playlist name'), 'My playlist')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith('My playlist', {
      folder_id: folder.id,
    }, [])
  })

  it('creates playlist with playables', async () => {
    const playables = h.factory('song', 3)
    const folder = h.factory('playlist-folder')
    const storeMock = h.mock(playlistStore, 'store').mockResolvedValue(h.factory('playlist'))

    h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: [ref({ folder, playables })],
        },
      },
    })

    screen.getByText('from 3 songs')

    await h.type(screen.getByPlaceholderText('Playlist name'), 'My playlist')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith('My playlist', {
      folder_id: folder.id,
    }, playables)
  })
})
