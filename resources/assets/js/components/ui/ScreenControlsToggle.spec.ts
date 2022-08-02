import isMobile from 'ismobilejs'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ScreenControlsToggle from './ScreenControlsToggle.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders and emits an event on mobile', async () => {
      isMobile.phone = true
      const { emitted, getByTestId } = this.render(ScreenControlsToggle)

      await fireEvent.click(getByTestId('controls-toggle'))

      expect(emitted().toggleControls).toBeTruthy()
    })
  }
}
