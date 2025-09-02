import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { ModalContextKey } from '@/symbols'
import { artistStore } from '@/stores/artistStore'
import Component from './EditArtistForm.vue'

describe('editArtistForm.vue', () => {
  const h = createHarness()

  const renderComponent = (artist?: Artist) => {
    artist = artist ?? h.factory('artist')
    artistStore.state.artists = [artist]

    const rendered = h.render(Component, {
      global: {
        provide: {
          [<symbol>ModalContextKey]: ref({ artist }),
        },
      },
    })

    return {
      ...rendered,
      artist,
    }
  }

  it('submits with no image change', async () => {
    const updateMock = h.mock(artistStore, 'update')
    const { artist } = renderComponent()

    // there should be a "remove cover" button, though we're not clicking it
    screen.getByRole('button', { name: 'Remove' })
    await h.type(screen.getByTitle('Artist name'), 'Dude')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(artist, {
      name: 'Dude',
      image: '',
    })
  })

  it('submits with a new image', async () => {
    const updateMock = h.mock(artistStore, 'update')
    const { artist } = renderComponent(h.factory('artist', { image: '' }))

    await h.type(screen.getByTitle('Artist name'), 'Dude')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    await h.user.upload(
      screen.getByLabelText('Pick an image (optional)'),
      new File(['bytes'], 'cover.png', { type: 'image/png' }),
    )

    await waitFor(() => expect(updateMock).toHaveBeenCalledWith(artist, {
      name: 'Dude',
      image: 'data:image/png;base64,Ynl0ZXM=',
    }))
  })

  it('removes image and submits', async () => {
    const { artist } = renderComponent(h.factory('artist'))
    const removeCoverMock = h.mock(artistStore, 'removeImage').mockResolvedValue(null)
    const updateMock = h.mock(artistStore, 'update')

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))
    expect(removeCoverMock).toHaveBeenCalledWith(artist)

    await h.type(screen.getByTitle('Artist name'), 'Dude')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(artist, {
      name: 'Dude',
      image: '',
    })
  })
})
