import { beforeEach, describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { albumStore } from '@/stores/albumStore'
import { encyclopediaService } from '@/services/encyclopediaService'
import Component from './EditAlbumForm.vue'

describe('editAlbumForm.vue', () => {
  const h = createHarness()

  beforeEach(() => {
    h.mock(encyclopediaService, 'fetchForAlbum').mockResolvedValue(null)
  })

  const renderComponent = (album?: Album) => {
    album = album ?? h.factory('album').make()
    albumStore.state.albums = [album]

    const rendered = h.render(Component, {
      props: {
        album,
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
      description: '',
    })
  })

  it('submits with a new cover', async () => {
    const updateMock = h.mock(albumStore, 'update')
    const { album } = renderComponent(h.factory('album').make())

    await h.type(screen.getByTitle('Album name'), 'Not So Good Actually')
    await h.type(screen.getByTitle('Release year'), '2022')

    await h.user.upload(
      screen.getByLabelText('Pick or paste a cover (optional)'),
      new File(['bytes'], 'cover.png', { type: 'image/png' }),
    )

    await waitFor(() => expect(screen.getByRole('img').getAttribute('src')).toBe('data:image/png;base64,Ynl0ZXM='))

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(album, {
      name: 'Not So Good Actually',
      year: 2022,
      cover: 'data:image/png;base64,Ynl0ZXM=',
      description: '',
    })
  })

  it('removes cover and submits', async () => {
    const { album } = renderComponent(h.factory('album').make())
    const updateMock = h.mock(albumStore, 'update')

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    await h.type(screen.getByTitle('Album name'), 'Not So Good Actually')
    await h.type(screen.getByTitle('Release year'), '2022')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(album, {
      name: 'Not So Good Actually',
      year: 2022,
      cover: '',
      description: '',
    })
  })

  it('saves no description when the pre-filled online text is left unchanged', async () => {
    h.mock(encyclopediaService, 'fetchForAlbum').mockResolvedValue({
      wiki: { summary: '', full: '<p>Found online</p>' },
    })
    const updateMock = h.mock(albumStore, 'update')
    renderComponent(h.factory('album').make({ description: null }))
    await h.tick(2)

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledWith(expect.anything(), expect.objectContaining({ description: '' }))
  })

  it('keeps the description written for the album without fetching the online one', async () => {
    const fetchMock = h.mock(encyclopediaService, 'fetchForAlbum')
    const updateMock = h.mock(albumStore, 'update')
    renderComponent(h.factory('album').make({ description: '<p>My own words</p>' }))
    await h.tick(2)

    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(fetchMock).not.toHaveBeenCalled()
    expect(updateMock).toHaveBeenCalledWith(
      expect.anything(),
      expect.objectContaining({ description: '<p>My own words</p>' }),
    )
  })
})
