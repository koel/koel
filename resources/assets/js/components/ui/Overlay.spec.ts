import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import { eventBus } from '@/utils'
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
    ])('renders %s type', async type => {
      const { getByTestId, html } = await this.renderComponent(type)

      expect(html()).toMatchSnapshot()
      expect((getByTestId('overlay') as HTMLDialogElement).open).toBe(true)
    })

    it('closes', async () => {
      const { getByTestId } = await this.renderComponent()

      eventBus.emit('HIDE_OVERLAY')
      expect((getByTestId('overlay') as HTMLDialogElement).open).toBe(false)
    })
  }
}
