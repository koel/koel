import { describe, expect, it } from 'vitest'
import { ref } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistStore } from '@/stores/playlistStore'
import { ModalContextKey } from '@/symbols'
import Component from './CreatePlaylistForm.vue'

describe('createPlaylistForm.vue', () => {
  const h = createHarness()

  const renderComponent = (folder?: PlaylistFolder, playables?: Playable[]) => {
    folder = folder ?? h.factory('playlist-folder')
    playables = playables ?? h.factory('song', 2)

    const rendered = h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ folder, playables }),
        },
      },
    })

    return {
      ...rendered,
      folder,
      playables,
    }
  }

  it('creates playlist with no playables', async () => {
    const { folder } = renderComponent(undefined, [])
    const storeMock = h.mock(playlistStore, 'store').mockResolvedValue(h.factory('playlist'))
    expect(screen.queryByTestId('from-playables')).toBeNull()

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'My playlist')
    await h.type(screen.getByRole('textbox', { name: 'description' }), 'Some description')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith({
      name: 'My playlist',
      description: 'Some description',
      folder_id: folder.id,
      cover: null,
    }, [])
  })

  it('creates playlist with playables', async () => {
    const storeMock = h.mock(playlistStore, 'store').mockResolvedValue(h.factory('playlist'))
    const { folder, playables } = renderComponent()

    screen.getByText(`from ${playables.length} songs`)

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'My playlist')
    await h.type(screen.getByRole('textbox', { name: 'description' }), 'Some description')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith({
      name: 'My playlist',
      description: 'Some description',
      folder_id: folder.id,
      cover: null,
    }, playables)
  })

  it('creates playlist with a cover', async () => {
    const { folder } = renderComponent(undefined, [])
    const storeMock = h.mock(playlistStore, 'store').mockResolvedValue(h.factory('playlist'))

    await h.user.upload(
      screen.getByLabelText('Pick a cover (optional)'),
      new File(['bytes'], 'logo.png', { type: 'image/png' }),
    )

    await waitFor(() => screen.getByAltText('Cover'))

    await h.type(screen.getByRole('textbox', { name: 'name' }), 'My playlist')
    await h.type(screen.getByRole('textbox', { name: 'description' }), 'Some description')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(storeMock).toHaveBeenCalledWith({
      name: 'My playlist',
      description: 'Some description',
      folder_id: folder.id,
      cover: 'data:image/png;base64,Ynl0ZXM=',
    }, [])
  })
})
