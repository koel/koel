import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ExtraPanelTabHeader from './ExtraPanelTabHeader.vue'
import { commonStore } from '@/stores'
import { fireEvent } from '@testing-library/vue'

new class extends UnitTestCase {
  protected test () {
    it('renders tab headers', () => {
      commonStore.state.use_you_tube = false
      const { getByTitle, queryByTitle } = this.render(ExtraPanelTabHeader)

      ;['Lyrics', 'Artist information', 'Album information'].forEach(title => getByTitle(title))
      expect(queryByTitle('Related YouTube videos')).toBeNull()
    })

    it('has a YouTube tab header if using YouTube', () => {
      commonStore.state.use_you_tube = true
      const { getByTitle } = this.render(ExtraPanelTabHeader)

      getByTitle('Related YouTube videos')
    })

    it('emits the selected tab value', async () => {
      const { getByTitle, emitted } = this.render(ExtraPanelTabHeader)

      await fireEvent.click(getByTitle('Lyrics'))

      expect(emitted()['update:modelValue']).toEqual([['Lyrics']])
    })
  }
}
