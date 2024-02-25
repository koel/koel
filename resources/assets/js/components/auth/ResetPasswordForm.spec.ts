import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { authService } from '@/services'
import ResetPasswordForm from './ResetPasswordForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('resets password', async () => {
      const resetMock = this.mock(authService, 'resetPassword').mockResolvedValue(null)

      await this.router.activateRoute({
        path: '_',
        screen: 'Password.Reset'
      }, { payload: 'Zm9vQGJhci5jb218bXktdG9rZW4=' })

      this.render(ResetPasswordForm)
      await this.type(screen.getByPlaceholderText('New password'), 'new-password')
      await this.user.click(screen.getByRole('button', { name: 'Save' }))

      expect(resetMock).toHaveBeenCalledWith('foo@bar.com', 'new-password', 'my-token')
    })
  }
}
