import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import type { UploadFile, UploadStatus } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import Btn from '@/components/ui/form/Btn.vue'
import UploadItem from './UploadItem.vue'

let file: UploadFile

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
    file = {
      status,
      file: new File([], 'sample.mp3'),
      id: 'x-file',
      message: '',
      name: 'Sample Track',
      progress: 42,
    }

    return this.render(UploadItem, {
      props: {
        file,
      },
      global: {
        stubs: {
          Btn,
        },
      },
    })
  }
}
