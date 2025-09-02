import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { ModalContextKey } from '@/symbols'
import { albumStore } from '@/stores/albumStore'
import Component from './EditAlbumForm.vue'

describe('editAlbumForm.vue', () => {
  const h = createHarness()

  const renderComponent = (album?: Album) => {
    album = album ?? h.factory('album')
    albumStore.state.albums = [album]

    const rendered = h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ album }),
        },
      },
    })

    return {
      ...rendered,
      album,
    }
  }

  it('submits with no cover change', async () => {
    const updateMock = h.mock(albumStore, 'update')
    const { album } = renderComponent()

    // there should be a "remove cover" button, though we're not clicking it
    screen.getByRole('button', { name: 'Remove' })
    await h.type(screen.getByTitle('Album name'), 'Not So Good Actually')
    await h.type(screen.getByTitle('Release year'), '2022')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(album, {
      name: 'Not So Good Actually',
      year: 2022,
      cover: '',
    })
  })

  it('submits with a new cover', async () => {
    const updateMock = h.mock(albumStore, 'update')
    const { album } = renderComponent(h.factory('album', { cover: '' }))

    await h.type(screen.getByTitle('Album name'), 'Not So Good Actually')
    await h.type(screen.getByTitle('Release year'), '2022')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await h.user.upload(
      screen.getByLabelText('Pick a cover (optional)'),
      new File(['bytes'], 'cover.png', { type: 'image/png' }),
    )

    await waitFor(() => expect(updateMock).toHaveBeenCalledWith(album, {
      name: 'Not So Good Actually',
      year: 2022,
      cover: 'data:image/png;base64,Ynl0ZXM=',
    }))
  })

  it('removes cover and submits', async () => {
    const { album } = renderComponent(h.factory('album'))
    const removeCoverMock = h.mock(albumStore, 'removeCover').mockResolvedValue(null)
    const updateMock = h.mock(albumStore, 'update')

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))
    expect(removeCoverMock).toHaveBeenCalledWith(album)

    await h.type(screen.getByTitle('Album name'), 'Not So Good Actually')
    await h.type(screen.getByTitle('Release year'), '2022')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(album, {
      name: 'Not So Good Actually',
      year: 2022,
      cover: '',
    })
  })
})
