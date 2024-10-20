import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils/eventBus'
import FooterExtraControls from './FooterExtraControls.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      this.setReadOnlyProperty(document, 'fullscreenEnabled', undefined)
      expect(this.renderComponent().html()).toMatchSnapshot()
    })

    it('toggles fullscreen mode', async () => {
      this.setReadOnlyProperty(document, 'fullscreenEnabled', true)
      this.renderComponent()
      const emitMock = this.mock(eventBus, 'emit')

      await this.user.click(screen.getByTitle('Enter fullscreen mode'))

      expect(emitMock).toHaveBeenCalledWith('FULLSCREEN_TOGGLE')
    })
  }

  private renderComponent () {
    return this.render(FooterExtraControls, {
      global: {
        stubs: {
          Equalizer: this.stub('Equalizer'),
          Volume: this.stub('Volume'),
        },
      },
    })
  }
}
