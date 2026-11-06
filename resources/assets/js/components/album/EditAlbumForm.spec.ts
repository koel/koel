import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { HTTPError } from 'ky'
import { albumStore } from '@/stores/albumStore'
import { encyclopediaService } from '@/services/encyclopediaService'
import { eventBus } from '@/utils/eventBus'
import Component from './EditAlbumForm.vue'

const mockShowConfirmDialog = vi.fn()

const mockToastError = vi.fn()

vi.mock('@/composables/useMessageToaster', () => ({
  useMessageToaster: () => ({
    toastLoading: vi.fn(() => 'toast-id'),
    toastError: mockToastError,
    toastSuccess: vi.fn(),
    updateToast: vi.fn(),
    dismissToast: vi.fn(),
  }),
}))

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({ showConfirmDialog: mockShowConfirmDialog, showErrorDialog: vi.fn() }),
}))

const createConflictError = () =>
  new HTTPError(new Response(null, { status: 409 }), new Request('http://test/api/test'), {} as any)

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
    await h.type(screen.getByPlaceholderText('Album name'), 'Not So Good Actually')
    await h.type(screen.getByLabelText('Release year'), '2022')
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

    await h.type(screen.getByPlaceholderText('Album name'), 'Not So Good Actually')
    await h.type(screen.getByLabelText('Release year'), '2022')

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

    await h.type(screen.getByPlaceholderText('Album name'), 'Not So Good Actually')
    await h.type(screen.getByLabelText('Release year'), '2022')
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

  it('merges into the existing album once confirmed', async () => {
    const existingAlbum = h.factory('album').make()
    const updateMock = h
      .mock(albumStore, 'update')
      .mockRejectedValueOnce(createConflictError())
      .mockResolvedValueOnce(existingAlbum)
    const emitMock = h.mock(eventBus, 'emit')
    mockShowConfirmDialog.mockResolvedValue(true)
    const { album } = renderComponent()

    await h.type(screen.getByPlaceholderText('Album name'), existingAlbum.name)
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(mockShowConfirmDialog).toHaveBeenCalledWith('An album with the same name already exists. Merge them?')
    expect(updateMock).toHaveBeenLastCalledWith(album, expect.objectContaining({ merge: true }))
    expect(emitMock).toHaveBeenCalledWith('ALBUM_MERGED', album, existingAlbum)
  })

  it('cancels quietly if the merge is not confirmed', async () => {
    const updateMock = h.mock(albumStore, 'update').mockRejectedValue(createConflictError())
    mockShowConfirmDialog.mockResolvedValue(false)
    renderComponent()

    await h.type(screen.getByPlaceholderText('Album name'), 'Taken Name')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))

    expect(updateMock).toHaveBeenCalledOnce()
    expect(mockToastError).not.toHaveBeenCalled()
  })
})
