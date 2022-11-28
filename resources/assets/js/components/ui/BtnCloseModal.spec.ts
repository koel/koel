import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import BtnCloseModal from './BtnCloseModal.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => expect(this.render(BtnCloseModal).html()).toMatchSnapshot())

    it('emits the event', async () => {
      const { emitted } = this.render(BtnCloseModal)

      await this.user.click(screen.getByRole('button'))

      expect(emitted().click).toBeTruthy()
    })
  }
}
