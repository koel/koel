import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import Router from '@/router'
import Component from './FooterQueueButton.vue'

new class extends UnitTestCase {
  protected test () {
    it('goes to queue screen', async () => {
      const goMock = this.mock(Router, 'go')
      this.render(Component)

      await this.user.click(screen.getByRole('button'))
      expect(goMock).toHaveBeenCalledWith('queue')
    })

    it('goes back if current screen is Queue', async () => {
      this.router.$currentRoute.value = {
        screen: 'Queue',
        path: '/queue'
      }

      const goMock = this.mock(Router, 'go')
      this.render(Component)

      await this.user.click(screen.getByRole('button'))
      expect(goMock).toHaveBeenCalledWith(-1)
    })
  }
}
