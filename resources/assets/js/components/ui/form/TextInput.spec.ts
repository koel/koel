import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import TextInput from './TextInput.vue'

new class extends UnitTestCase {
  protected test () {
    it('emits value', async () => {
      const { emitted } = this.render(TextInput)

      await this.type(screen.getByRole('textbox'), 'Hi')

      expect(emitted()['update:modelValue']).toStrictEqual([
        ['H'],
        ['Hi']
      ])
    })
  }
}
