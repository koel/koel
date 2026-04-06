import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { uploadService } from '@/services/uploadService'
import type { DuplicateUpload } from '@/services/uploadService'
import Component from './DuplicateUploadList.vue'

const mockShowConfirmDialog = vi.fn()

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showConfirmDialog: mockShowConfirmDialog,
  }),
}))

describe('duplicateUploadList', () => {
  const h = createHarness({
    beforeEach: () => mockShowConfirmDialog.mockClear(),
  })

  const makeSongs = (count = 2): DuplicateUpload[] =>
    Array.from({ length: count }, (_, i) => ({
      type: 'duplicate-uploads' as const,
      id: `dup-${i}`,
      song_title: `Song ${i}`,
      artist_name: `Artist ${i}`,
      filename: `song-${i}.mp3`,
      created_at: '2026-04-01T00:00:00.000000Z',
    }))

  const renderComponent = (songs = makeSongs()) =>
    h.render(Component, {
      props: { songs },
      global: {
        stubs: {
          DuplicateUploadItem: {
            template: '<div data-testid="dup-item" />',
            props: ['upload'],
          },
        },
      },
    })

  it('renders the count badge', () => {
    renderComponent(makeSongs(3))
    screen.getByText('3')
  })

  it('pluralizes the label correctly for single item', () => {
    renderComponent(makeSongs(1))
    screen.getByText('Duplicate file')
  })

  it('pluralizes the label correctly for multiple items', () => {
    renderComponent(makeSongs(3))
    screen.getByText('Duplicate files')
  })

  it('calls keepAllDuplicates on keep all button click', async () => {
    const mock = h.mock(uploadService, 'keepAllDuplicates')
    renderComponent()

    // Open details
    await h.user.click(screen.getByText('Duplicate files'))
    await h.user.click(screen.getByRole('button', { name: 'Keep All' }))

    expect(mock).toHaveBeenCalled()
  })

  it('confirms before discarding all', async () => {
    mockShowConfirmDialog.mockResolvedValue(true)
    const mock = h.mock(uploadService, 'discardAllDuplicates')
    renderComponent()

    await h.user.click(screen.getByText('Duplicate files'))
    await h.user.click(screen.getByRole('button', { name: 'Discard All' }))

    expect(mockShowConfirmDialog).toHaveBeenCalledWith('Discard all duplicate uploads?')
    expect(mock).toHaveBeenCalled()
  })
})
