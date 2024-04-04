import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { screen } from '@testing-library/vue'
import CheckBox from './CheckBox.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders unchecked state', () => expect(this.render(CheckBox).html()).toMatchSnapshot())

    it('renders checked state', () => expect(this.render(CheckBox, {
      props: {
        modelValue: true
      }
    }).html()).toMatchSnapshot())

    it('emits the input event', async () => {
      const { emitted } = this.render(CheckBox)

      await this.user.click(screen.getByRole('checkbox'))

      expect(emitted()['update:modelValue']).toBeTruthy()
    })
  }
}
