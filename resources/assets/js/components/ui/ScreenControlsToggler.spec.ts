import isMobile from 'ismobilejs'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import ScreenControlsToggler from './ScreenControlsToggler.vue'

new class extends ComponentTestCase {
  protected test () {
    it('renders and emits an event on mobile', async () => {
      isMobile.phone = true
      const { emitted, getByTestId } = this.render(ScreenControlsToggler)

      await fireEvent.click(getByTestId('controls-toggler'))

      expect(emitted().toggleControls).toBeTruthy()
    })
  }
}
