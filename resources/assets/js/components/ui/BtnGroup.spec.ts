import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import BtnGroup from './BtnGroup.vue'
import Btn from './Btn.vue'

new class extends ComponentTestCase {
  private renderButtonToSlot (text: string) {
    return this.render(Btn, {
      slots: {
        default: text
      }
    }).html()
  }

  protected test () {
    it('renders', () => {
      expect(this.render(BtnGroup, {
        slots: {
          default: ['Green', 'Orange', 'Blue'].map(text => this.renderButtonToSlot(text))
        }
      }).html()).toMatchSnapshot()
    })
  }
}
