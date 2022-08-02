import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import AppleMusicButton from './AppleMusicButton.vue'

new class extends UnitTestCase {
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
