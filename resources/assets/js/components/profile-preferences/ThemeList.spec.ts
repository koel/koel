import { expect, it } from 'vitest'
import { themeStore } from '@/stores'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import ThemeList from './ThemeList.vue'

new class extends ComponentTestCase {
  protected test () {
    it('displays all themes', () => {
      expect(this.render(ThemeList).getAllByTestId('theme-card').length).toEqual(themeStore.state.themes.length)
    })
  }
}
