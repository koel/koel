import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import BtnGroup from './BtnGroup.vue'
import Btn from './Btn.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(BtnGroup, {
        slots: {
          default: ['Green', 'Orange', 'Blue'].map(text => this.renderButtonToSlot(text))
        }
      }).html()).toMatchSnapshot()
    })
  }

  private renderButtonToSlot (text: string) {
    return this.render(Btn, {
      slots: {
        default: text
      }
    }).html()
  }
}
