import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import themes from '@/themes'
import { themeStore } from '@/stores/themeStore'
import Component from './ThemeList.vue'

new class extends UnitTestCase {
  protected test () {
    it('displays all themes', () => {
      themeStore.init()
      expect(this.render(Component).getAllByTestId('theme-card').length).toEqual(themes.length)
    })
  }
}
