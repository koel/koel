import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { uploadService } from '@/services/uploadService'
import type { DuplicateUpload } from '@/services/uploadService'
import Component from './DuplicateUploadItem.vue'

const mockShowConfirmDialog = vi.fn()

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showConfirmDialog: mockShowConfirmDialog,
  }),
}))

describe('duplicateUploadItem', () => {
  const h = createHarness({
    beforeEach: () => mockShowConfirmDialog.mockClear(),
  })

  const makeUpload = (overrides: Partial<DuplicateUpload> = {}): DuplicateUpload => ({
    type: 'duplicate-uploads',
    id: 'dup-1',
    song_title: 'Test Song',
    artist_name: 'Test Artist',
    filename: 'test-song.mp3',
    created_at: '2026-04-01T00:00:00.000000Z',
    ...overrides,
  })

  const renderComponent = (upload = makeUpload()) => h.render(Component, { props: { upload } })

  it('renders song title and artist when available', () => {
    renderComponent()
    screen.getByText('Test Artist — Test Song')
  })

  it('falls back to filename when song title is null', () => {
    renderComponent(makeUpload({ song_title: null, artist_name: null }))
    screen.getByText('test-song.mp3')
  })

  it('calls keepDuplicate on keep button click', async () => {
    const mock = h.mock(uploadService, 'keepDuplicate')
    renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Keep' }))

    expect(mock).toHaveBeenCalledWith('dup-1')
  })

  it('confirms before discarding', async () => {
    mockShowConfirmDialog.mockResolvedValue(true)
    const mock = h.mock(uploadService, 'discardDuplicate')
    renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Discard' }))

    expect(mockShowConfirmDialog).toHaveBeenCalledWith('Discard this duplicate upload?')
    expect(mock).toHaveBeenCalledWith('dup-1')
  })

  it('does not discard when confirmation is declined', async () => {
    mockShowConfirmDialog.mockResolvedValue(false)
    const mock = h.mock(uploadService, 'discardDuplicate')
    renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Discard' }))

    expect(mock).not.toHaveBeenCalled()
  })
})
