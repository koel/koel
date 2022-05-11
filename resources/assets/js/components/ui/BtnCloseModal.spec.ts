import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import BtnCloseModal from './BtnCloseModal.vue'

new class extends ComponentTestCase {
  protected test () {
    it('renders', () => expect(this.render(BtnCloseModal).html()).toMatchSnapshot())

    it('emits the event', async () => {
      const { emitted, getByRole } = this.render(BtnCloseModal)

      await fireEvent.click(getByRole('button'))

      expect(emitted().click).toBeTruthy()
    })
  }
}
