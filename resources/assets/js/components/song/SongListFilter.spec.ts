import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SongListFilter from './SongListFilter.vue'

new class extends UnitTestCase {
  protected test() {
    it('emit an event on input', async () => {
      const { emitted } = this.render(SongListFilter)

      await this.user.type(screen.getByPlaceholderText('Keywords'), 'cat')

      expect(emitted().change).toEqual([['c'], ['ca'], ['cat']])
    })
  }
}
