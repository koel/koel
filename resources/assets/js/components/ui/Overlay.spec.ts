import ComponentTestCase from '@/__tests__/ComponentTestCase'
import { expect, it } from 'vitest'
import { OverlayState } from 'koel/types/ui'
import { eventBus } from '@/utils'
import Overlay from './Overlay.vue'
import SoundBar from '@/components/ui/SoundBar.vue'

new class extends ComponentTestCase {
  private async showOverlay (type: OverlayState['type'] = 'loading') {
    const rendered = this.render(Overlay, {
      global: {
        stubs: {
          SoundBar
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
    ])('renders %s type', async (type) => expect((await this.showOverlay(type)).html()).toMatchSnapshot())

    it('closes', async () => {
      const { queryByTestId } = await this.showOverlay()
      expect(await queryByTestId('overlay')).not.toBeNull()

      eventBus.emit('HIDE_OVERLAY')
      await this.tick()

      expect(await queryByTestId('overlay')).toBeNull()
    })
  }
}
