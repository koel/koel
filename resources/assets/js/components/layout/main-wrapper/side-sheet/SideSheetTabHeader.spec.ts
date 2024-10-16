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

      ;['Lyrics', 'Artist information', 'Album information'].forEach(name => screen.getByRole('button', { name }))
      expect(screen.queryByRole('button', { name: 'Related YouTube videos' })).toBeNull()
    })

    it('has a YouTube tab header if using YouTube', () => {
      commonStore.state.uses_you_tube = true
      this.render(Component)

      screen.getByRole('button', { name: 'Related YouTube videos' })
    })

    it('emits the selected tab value', async () => {
      const { emitted } = this.render(Component)

      await this.user.click(screen.getByRole('button', { name: 'Lyrics' }))

      expect(emitted()['update:modelValue']).toEqual([['Lyrics']])
    })
  }
}
