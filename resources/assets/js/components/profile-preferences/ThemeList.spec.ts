import { expect, it } from 'vitest'
import { themeStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import themes from '@/themes'
import ThemeList from './ThemeList.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays all themes', () => {
      themeStore.init()
      expect(this.render(ThemeList).getAllByTestId('theme-card').length).toEqual(themes.length)
    })
  }
}
