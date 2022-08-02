import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import CheckBox from './CheckBox.vue'
import { fireEvent } from '@testing-library/vue'

new class extends UnitTestCase {
  protected test () {
    it('renders unchecked state', () => expect(this.render(CheckBox).html()).toMatchSnapshot())

    it('renders checked state', () => expect(this.render(CheckBox, {
      props: {
        modelValue: true
      }
    }).html()).toMatchSnapshot())

    it('emits the input event', async () => {
      const { getByRole, emitted } = this.render(CheckBox)

      await fireEvent.input(getByRole('checkbox'))

      expect(emitted()['update:modelValue']).toBeTruthy()
    })
  }
}
