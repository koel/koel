import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import CheckBox from './CheckBox.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders unchecked state', () => expect(this.render(CheckBox).html()).toMatchSnapshot())

    it('renders checked state', () => expect(this.render(CheckBox, {
      props: {
        modelValue: true,
      },
    }).html()).toMatchSnapshot())

    it('emits the input event', async () => {
      const { emitted } = this.render(CheckBox)

      await this.trigger(screen.getByRole('checkbox'), 'click')

      expect(emitted()['update:modelValue']).toBeTruthy()
    })
  }
}
