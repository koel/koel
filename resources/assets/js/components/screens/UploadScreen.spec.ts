import { beforeEach, describe, expect, it, vi } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { uploadService } from '@/services/uploadService'
import Component from './UploadScreen.vue'

const queueFilesForUploadMock = vi.fn()
const handleDropEventMock = vi.fn()

vi.mock('@/utils/mediaHelper', () => ({
  acceptedExtensions: ['mp3', 'flac', 'ogg'],
}))

vi.mock('@/composables/useUpload', () => ({
  useUpload: () => ({
    allowsUpload: { value: true },
    mediaPathSetUp: { value: true },
    queueFilesForUpload: queueFilesForUploadMock,
    handleDropEvent: handleDropEventMock,
  }),
}))

describe('uploadScreen.vue', () => {
  const h = createHarness()

  beforeEach(() => {
    vi.spyOn(uploadService, 'fetchDuplicates').mockResolvedValue(undefined)
    uploadService.state.duplicatedSongs = []
  })

  it('renders the upload header', () => {
    h.render(Component)
    screen.getByText('Upload Media')
  })

  it('shows empty state when no files are queued', () => {
    uploadService.state.files = []
    h.render(Component)
    screen.getByText(/Drop files.*to upload/)
    screen.getByText('or click here to select songs')
  })

  it('renders upload items when files are queued', async () => {
    uploadService.state.files = [
      { id: '1', file: new File([], 'song.mp3'), status: 'Uploading', name: 'song.mp3', progress: 50 },
      { id: '2', file: new File([], 'track.flac'), status: 'Ready', name: 'track.flac', progress: 0 },
    ]

    h.render(Component)

    await waitFor(() => expect(screen.getAllByTestId('upload-item')).toHaveLength(2))
    expect(screen.queryByText(/Drop files.*to upload/)).toBeNull()
  })

  it('shows retry and remove buttons when there are failures', async () => {
    uploadService.state.files = [
      { id: '1', file: new File([], 'bad.mp3'), status: 'Errored', name: 'bad.mp3', progress: 0 },
    ]

    h.render(Component)
    await h.user.click(await screen.findByTestId('upload-tab-errored'))

    await waitFor(() => {
      screen.getByTestId('upload-retry-all-btn')
      screen.getByTestId('upload-remove-all-btn')
    })
  })

  it('does not show retry and remove buttons when there are no failures', () => {
    uploadService.state.files = [
      { id: '1', file: new File([], 'good.mp3'), status: 'Uploaded', name: 'good.mp3', progress: 100 },
    ]

    h.render(Component)

    expect(screen.queryByTestId('upload-retry-all-btn')).toBeNull()
    expect(screen.queryByTestId('upload-remove-all-btn')).toBeNull()
  })

  it('retries all failed uploads', async () => {
    const retryAllMock = h.mock(uploadService, 'retryAll')

    uploadService.state.files = [
      { id: '1', file: new File([], 'bad.mp3'), status: 'Errored', name: 'bad.mp3', progress: 0 },
    ]

    h.render(Component)
    await h.user.click(await screen.findByTestId('upload-tab-errored'))

    await h.user.click(await screen.findByTestId('upload-retry-all-btn'))

    expect(retryAllMock).toHaveBeenCalled()
  })

  it('removes failed entries', async () => {
    const removeFailedMock = h.mock(uploadService, 'removeFailed')

    uploadService.state.files = [
      { id: '1', file: new File([], 'bad.mp3'), status: 'Errored', name: 'bad.mp3', progress: 0 },
    ]

    h.render(Component)
    await h.user.click(await screen.findByTestId('upload-tab-errored'))

    await h.user.click(await screen.findByTestId('upload-remove-all-btn'))

    expect(removeFailedMock).toHaveBeenCalled()
  })

  it('sorts the files into tabs by state', async () => {
    uploadService.state.files = [
      { id: '1', file: new File([], 'done.mp3'), status: 'Uploaded', name: 'done.mp3', progress: 100 },
      { id: '2', file: new File([], 'going.mp3'), status: 'Uploading', name: 'going.mp3', progress: 30 },
      { id: '3', file: new File([], 'queued.mp3'), status: 'Ready', name: 'queued.mp3', progress: 0 },
      { id: '4', file: new File([], 'notes.txt'), status: 'Skipped', name: 'notes.txt', progress: 0 },
      { id: '5', file: new File([], 'bad.mp3'), status: 'Canceled', name: 'bad.mp3', progress: 0 },
    ]

    h.render(Component)

    expect((await screen.findByTestId('upload-tab-in-progress')).dataset.count).toBe('2')
    expect(screen.getByTestId('upload-tab-done').dataset.count).toBe('1')
    expect(screen.getByTestId('upload-tab-skipped').dataset.count).toBe('1')
    expect(screen.getByTestId('upload-tab-errored').dataset.count).toBe('1')
    await waitFor(() => expect(screen.getAllByTestId('upload-item')).toHaveLength(2))

    await h.user.click(screen.getByTestId('upload-tab-done'))

    await waitFor(() => expect(screen.getAllByTestId('upload-item')).toHaveLength(1))
    expect(screen.queryByTestId('upload-retry-all-btn')).toBeNull()
  })

  it('renders only the rows in view for a long list', async () => {
    uploadService.state.files = Array.from({ length: 100 }, (_, i) => ({
      id: `${i}`,
      file: new File([], `song-${i}.mp3`),
      status: 'Ready' as const,
      name: `song-${i}.mp3`,
      progress: 0,
    }))

    h.render(Component)

    await waitFor(() => expect(screen.getAllByTestId('upload-item').length).toBeGreaterThan(0))
    expect(screen.getAllByTestId('upload-item').length).toBeLessThan(100)
  })

  it('offers the drop prompt while nothing is in progress', async () => {
    uploadService.state.files = [
      { id: '1', file: new File([], 'done.mp3'), status: 'Uploaded', name: 'done.mp3', progress: 100 },
    ]

    h.render(Component)

    await screen.findByTestId('upload-drop-prompt')

    await h.user.click(screen.getByTestId('upload-tab-done'))

    expect(screen.queryByTestId('upload-drop-prompt')).toBeNull()
  })
})
