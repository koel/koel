import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import type { UploadStatus } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import Btn from '@/components/ui/form/Btn.vue'
import Component from './UploadItem.vue'

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

  it.each<[UploadStatus]>([
    ['Uploaded'],
    ['Errored'],
    ['Canceled'],
  ])('allows removal if not uploading', async status => {
    const mock = h.mock(uploadService, 'remove')
    renderComponent(status)

    await h.user.click(screen.getByRole('button', { name: 'Remove' }))

    expect(mock).toHaveBeenCalled()
  })
})
