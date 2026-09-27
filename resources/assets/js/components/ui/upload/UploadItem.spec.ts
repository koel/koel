import { describe, expect, it, vi } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import type { UploadStatus } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import Btn from '@/components/ui/form/Btn.vue'
import Component from './UploadItem.vue'

const mockShowConfirmDialog = vi.fn()

vi.mock('@/composables/useDialogBox', () => ({
  useDialogBox: () => ({
    showConfirmDialog: mockShowConfirmDialog,
  }),
}))

describe('uploadItem.vue', () => {
  const h = createHarness()

  const renderComponent = (status: UploadStatus) => {
    const file = {
      status,
      file: new File([], 'sample.mp3'),
      id: 'x-file',
      message: '',
      name: 'Sample Track',
      progress: 42,
    }

    const rendered = h.render(Component, {
      props: {
        file,
      },
      global: {
        stubs: {
          Btn,
        },
      },
    })

    return {
      ...rendered,
      file,
    }
  }

  it('renders', () => expect(renderComponent('Canceled').html()).toMatchSnapshot())

  it.each<[UploadStatus]>([['Canceled'], ['Errored']])('allows retrying when %s', async status => {
    const mock = h.mock(uploadService, 'retry')
    renderComponent(status)

    await h.user.click(screen.getByRole('button', { name: 'Retry' }))

    expect(mock).toHaveBeenCalled()
  })

  it.each<[UploadStatus]>([['Errored'], ['Canceled']])('allows removing a file that did not upload', async status => {
    const mock = h.mock(uploadService, 'remove')
    renderComponent(status)

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    expect(mock).toHaveBeenCalled()
  })

  it('aborts upload after confirmation', async () => {
    mockShowConfirmDialog.mockResolvedValue(true)
    const mock = h.mock(uploadService, 'abort')
    renderComponent('Uploading')

    await h.user.click(screen.getByRole('button', { name: 'Abort' }))

    expect(mockShowConfirmDialog).toHaveBeenCalledWith('Abort this upload?')
    expect(mock).toHaveBeenCalled()
  })

  it('does not abort upload if confirmation is declined', async () => {
    mockShowConfirmDialog.mockResolvedValue(false)
    const mock = h.mock(uploadService, 'abort')
    renderComponent('Uploading')

    await h.user.click(screen.getByRole('button', { name: 'Abort' }))

    expect(mockShowConfirmDialog).toHaveBeenCalledWith('Abort this upload?')
    expect(mock).not.toHaveBeenCalled()
  })

  it('does not abort if upload completed while confirming', async () => {
    mockShowConfirmDialog.mockImplementation(async () => {
      // Simulate the upload finishing while the dialog is open
      renderResult.file.status = 'Uploaded'
      return true
    })
    const mock = h.mock(uploadService, 'abort')
    const renderResult = renderComponent('Uploading')

    await h.user.click(screen.getByRole('button', { name: 'Abort' }))

    expect(mock).not.toHaveBeenCalled()
  })

  it('does not show remove button when uploading', () => {
    renderComponent('Uploading')

    expect(screen.queryByRole('button', { name: 'Remove' })).toBeNull()
  })

  it('does not show remove button while processing', () => {
    renderComponent('Processing')

    expect(screen.queryByRole('button', { name: 'Remove' })).toBeNull()
  })

  it('marks an uploaded file with a check', () => {
    renderComponent('Uploaded')

    screen.getByTitle('Uploaded')
  })

  it('shows a spinner while processing', () => {
    renderComponent('Processing')

    screen.getByTitle('Processing')
  })

  it('shows the upload progress as a ring while uploading', () => {
    renderComponent('Uploading')

    expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('42')
  })

  it('shows an empty progress ring for a queued file', () => {
    renderComponent('Ready')

    expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('0')
  })

  it.each<[UploadStatus]>([['Processing'], ['Uploaded'], ['Errored']])(
    'shows no progress ring for a %s file',
    status => {
      renderComponent(status)

      expect(screen.queryByRole('progressbar')).toBeNull()
    },
  )

  it('links an uploaded song to its album', () => {
    const song = h.factory('song').make()

    h.render(Component, {
      props: {
        file: {
          status: 'Uploaded',
          file: new File([], 'sample.mp3'),
          id: 'x-file',
          name: 'Sample Track',
          progress: 100,
          song,
        },
      },
    })

    expect(screen.getByTestId('upload-item-album-link').getAttribute('href')).toContain(song.album_id)
  })

  it.each<[UploadStatus]>([['Skipped'], ['Uploaded']])('offers no removal for a %s file', status => {
    renderComponent(status)

    expect(screen.queryByRole('button', { name: 'Remove' })).toBeNull()
  })

  it.each<[UploadStatus]>([['Skipped'], ['Errored'], ['Canceled']])(
    'shows why a %s file did not upload in its row',
    status => {
      renderComponent(status)

      screen.getByTestId('upload-item-reason')
    },
  )
})
