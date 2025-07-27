import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Component from './CheckBox.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders unchecked state', () => expect(this.render(Component).html()).toMatchSnapshot())

    it('renders checked state', () => expect(this.render(Component, {
      props: {
        modelValue: true,
      },
    }).html()).toMatchSnapshot())

    it('emits the input event', async () => {
      const { emitted } = this.render(Component)

      await this.trigger(screen.getByRole('checkbox'), 'click')

      expect(emitted()['update:modelValue']).toBeTruthy()
    })
  }
}
