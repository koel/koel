import { beforeEach, describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { artistStore } from '@/stores/artistStore'
import { encyclopediaService } from '@/services/encyclopediaService'
import Component from './EditArtistForm.vue'

describe('editArtistForm.vue', () => {
  const h = createHarness()

  beforeEach(() => {
    h.mock(encyclopediaService, 'fetchForArtist').mockResolvedValue(null)
  })

  const renderComponent = (artist?: Artist) => {
    artist = artist ?? h.factory('artist').make()
    artistStore.state.artists = [artist]

    const rendered = h.render(Component, {
      props: {
        artist,
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
      description: '',
    })
  })

  it('submits with a new image', async () => {
    const updateMock = h.mock(artistStore, 'update')
    const { artist } = renderComponent(h.factory('artist').make({ image: '' }))

    await h.type(screen.getByTitle('Artist name'), 'Dude')

    await h.user.upload(
      screen.getByLabelText('Pick or paste an image (optional)'),
      new File(['bytes'], 'cover.png', { type: 'image/png' }),
    )

    await waitFor(() => screen.getByRole('img'))

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(artist, {
      name: 'Dude',
      image: 'data:image/png;base64,Ynl0ZXM=',
      description: '',
    })
  })

  it('removes image and submits', async () => {
    const { artist } = renderComponent(h.factory('artist').make())
    const updateMock = h.mock(artistStore, 'update')

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    await h.type(screen.getByTitle('Artist name'), 'Dude')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(artist, {
      name: 'Dude',
      image: '',
      description: '',
    })
  })

  it('saves no description when the pre-filled online text is left unchanged', async () => {
    h.mock(encyclopediaService, 'fetchForArtist').mockResolvedValue({
      bio: { summary: '', full: '<p>Found online</p>' },
    })
    const updateMock = h.mock(artistStore, 'update')
    renderComponent(h.factory('artist').make({ description: null }))
    await h.tick(2)

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(expect.anything(), expect.objectContaining({ description: '' }))
  })

  it('keeps the description written for the artist without fetching the online one', async () => {
    const fetchMock = h.mock(encyclopediaService, 'fetchForArtist')
    const updateMock = h.mock(artistStore, 'update')
    renderComponent(h.factory('artist').make({ description: '<p>My own words</p>' }))
    await h.tick(2)

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(fetchMock).not.toHaveBeenCalled()
    expect(updateMock).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({ description: '<p>My own words</p>' }),
    )
  })
})
