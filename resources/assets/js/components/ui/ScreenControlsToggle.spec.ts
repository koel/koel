import isMobile from 'ismobilejs'
import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ScreenControlsToggle from './ScreenControlsToggle.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders and emits an event on mobile', async () => {
      isMobile.phone = true
      const { emitted } = this.render(ScreenControlsToggle)

      await this.user.click(screen.getByRole('checkbox'))

      expect(emitted()['update:modelValue']).toBeTruthy()
    })
  }
}
