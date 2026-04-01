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

  it.each<[UploadStatus]>([['Uploaded'], ['Errored'], ['Canceled']])(
    'allows removal if not uploading',
    async status => {
      const mock = h.mock(uploadService, 'remove')
      renderComponent(status)

      await h.user.click(screen.getByRole('button', { name: 'Remove' }))

      expect(mock).toHaveBeenCalled()
    },
  )

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
})
