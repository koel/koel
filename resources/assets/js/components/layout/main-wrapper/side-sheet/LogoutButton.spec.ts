import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import LogoutButton from './LogoutButton.vue'

new class extends UnitTestCase {
  protected test () {
    it('emits the logout event', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      this.render(LogoutButton)

      await this.user.click(screen.getByRole('button'))

      expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
    })
  }
}
