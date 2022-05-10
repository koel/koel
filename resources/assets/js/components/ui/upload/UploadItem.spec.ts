import { expect, it } from 'vitest'
import { UploadFile, UploadStatus } from '@/config'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import { fireEvent } from '@testing-library/vue'
import { uploadService } from '@/services'
import Btn from '@/components/ui/Btn.vue'
import UploadItem from './UploadItem.vue'

let file: UploadFile

new class extends ComponentTestCase {
  private renderComponent (status: UploadStatus) {
    file = {
      status,
      file: new File([], 'sample.mp3'),
      id: 'x-file',
      message: '',
      name: 'Sample Track',
      progress: 42
    }

    return this.render(UploadItem, {
      props: {
        file
      },
      global: {
        stubs: {
          Btn
        }
      }
    })
  }

  protected test () {
    it('renders', () => expect(this.renderComponent('Canceled').html()).toMatchSnapshot())

    it.each<[UploadStatus]>([['Canceled'], ['Errored']])('allows retrying when %s', async (status) => {
      const mock = this.mock(uploadService, 'retry')
      const { getByTitle } = this.renderComponent(status)

      await fireEvent.click(getByTitle('Retry'))

      expect(mock).toHaveBeenCalled()
    })

    it.each<[UploadStatus]>([
      ['Uploaded'],
      ['Errored'],
      ['Canceled']]
    )('allows removal if not uploading', async (status) => {
      const mock = this.mock(uploadService, 'remove')
      const { getByTitle } = this.renderComponent(status)

      await fireEvent.click(getByTitle('Remove'))

      expect(mock).toHaveBeenCalled()
    })
  }
}
