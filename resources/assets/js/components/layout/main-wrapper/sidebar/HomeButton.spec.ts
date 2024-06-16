import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import Component from './HomeButton.vue'

new class extends UnitTestCase {
  protected test () {
    it('triggers the sidebar toggle event', async () => {
      this.mock(eventBus, 'emit')
      this.render(Component)
      await this.user.click(screen.getByRole('link'))

      expect(eventBus.emit).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
    })
  }
}
