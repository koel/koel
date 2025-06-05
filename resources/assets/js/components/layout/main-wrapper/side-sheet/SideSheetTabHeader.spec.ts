import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore } from '@/stores/commonStore'
import Component from './SideSheetTabHeader.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders tab headers', () => {
      commonStore.state.uses_you_tube = false
      this.render(Component)

      ;['lyrics', 'artist', 'album'].forEach(name => screen.getByTestId(`side-sheet-${name}-tab-header`))
      expect(screen.queryByTestId('side-sheet-youtube-tab-header')).toBeNull()
    })

    it('has a YouTube tab header if using YouTube', () => {
      commonStore.state.uses_you_tube = true
      this.render(Component)

      screen.getByTestId('side-sheet-youtube-tab-header')
    })

    it('emits the selected tab value', async () => {
      const { emitted } = this.render(Component)

      await this.user.click(screen.getByTestId('side-sheet-lyrics-tab-header'))

      expect(emitted()['update:modelValue']).toEqual([['Lyrics']])
    })
  }
}
