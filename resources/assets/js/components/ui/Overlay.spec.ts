import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import { eventBus } from '@/utils'
import { waitFor } from '@testing-library/vue'
import SoundBars from '@/components/ui/SoundBars.vue'
import Overlay from './Overlay.vue'

new class extends UnitTestCase {
  private async renderComponent (type: OverlayState['type'] = 'loading') {
    const rendered = this.render(Overlay, {
      global: {
        stubs: {
          SoundBars
        }
      }
    })

    eventBus.emit('SHOW_OVERLAY', {
      type,
      message: 'Look at me now'
    })

    await this.tick()

    return rendered
  }

  protected test () {
    it.each<[OverlayState['type']]>([
      ['loading'],
      ['success'],
      ['info'],
      ['warning'],
      ['error']
    ])('renders %s type', async (type) => expect((await this.renderComponent(type)).html()).toMatchSnapshot())

    it('closes', async () => {
      const { queryByTestId } = await this.renderComponent()
      expect(queryByTestId('overlay')).not.toBeNull()

      eventBus.emit('HIDE_OVERLAY')
      await waitFor(() => expect(queryByTestId('overlay')).toBeNull())
    })
  }
}
