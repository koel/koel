import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { fireEvent } from '@testing-library/vue'
import Magnifier from './Magnifier.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders and functions', async () => {
      const { getByTitle, html, emitted } = this.render(Magnifier)

      await fireEvent.click(getByTitle('Zoom in'))
      expect(emitted()['in']).toBeTruthy()

      await fireEvent.click(getByTitle('Zoom out'))
      expect(emitted().out).toBeTruthy()

      expect(html()).toMatchSnapshot()
    })
  }
}
