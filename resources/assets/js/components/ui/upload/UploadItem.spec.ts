import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import type { UploadStatus } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import Btn from '@/components/ui/form/Btn.vue'
import Component from './UploadItem.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.renderComponent('Canceled').html()).toMatchSnapshot())

    it.each<[UploadStatus]>([['Canceled'], ['Errored']])('allows retrying when %s', async status => {
      const mock = this.mock(uploadService, 'retry')
      this.renderComponent(status)

      await this.user.click(screen.getByRole('button', { name: 'Retry' }))

      expect(mock).toHaveBeenCalled()
    })

    it.each<[UploadStatus]>([
      ['Uploaded'],
      ['Errored'],
      ['Canceled'],
    ],
    )('allows removal if not uploading', async status => {
      const mock = this.mock(uploadService, 'remove')
      this.renderComponent(status)

      await this.user.click(screen.getByRole('button', { name: 'Remove' }))

      expect(mock).toHaveBeenCalled()
    })
  }

  private renderComponent (status: UploadStatus) {
    const file = {
      status,
      file: new File([], 'sample.mp3'),
      id: 'x-file',
      message: '',
      name: 'Sample Track',
      progress: 42,
    }

    const rendered = this.render(Component, {
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
}
