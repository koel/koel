import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import AppleMusicButton from './AppleMusicButton.vue'

new class extends ComponentTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(AppleMusicButton, {
        props: {
          url: 'https://music.apple.com/buy-nao'
        }
      }).html()).toMatchSnapshot()
    })
  }
}
