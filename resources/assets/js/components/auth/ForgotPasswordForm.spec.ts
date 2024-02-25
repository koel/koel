import { screen } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { authService } from '@/services'
import ForgotPasswordForm from './ForgotPasswordForm.vue'

new class extends UnitTestCase {
  protected test () {
    it('requests reset password link', async () => {
      const requestMock = this.mock(authService, 'requestResetPasswordLink').mockResolvedValue(null)
      this.render(ForgotPasswordForm)
      await this.type(screen.getByPlaceholderText('Your email address'), 'foo@bar.com')
      await this.user.click(screen.getByText('Reset Password'))

      expect(requestMock).toHaveBeenCalledWith('foo@bar.com')
    })

    it('cancels', async () => {
      const { emitted } = this.render(ForgotPasswordForm)
      await this.user.click(screen.getByText('Cancel'))

      expect(emitted().cancel).toBeTruthy()
    })
  }
}
