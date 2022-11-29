import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore } from '@/stores'
import { screen } from '@testing-library/vue'
import ExtraPanelTabHeader from './ExtraPanelTabHeader.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders tab headers', () => {
      commonStore.state.use_you_tube = false
      this.render(ExtraPanelTabHeader)

      ;['Lyrics', 'Artist information', 'Album information'].forEach(name => screen.getByRole('button', { name }))
      expect(screen.queryByRole('button', { name: 'Related YouTube videos' })).toBeNull()
    })

    it('has a YouTube tab header if using YouTube', () => {
      commonStore.state.use_you_tube = true
      this.render(ExtraPanelTabHeader)

      screen.getByRole('button', { name: 'Related YouTube videos' })
    })

    it('emits the selected tab value', async () => {
      const { emitted } = this.render(ExtraPanelTabHeader)

      await this.user.click(screen.getByRole('button', { name: 'Lyrics' }))

      expect(emitted()['update:modelValue']).toEqual([['Lyrics']])
    })
  }
}
