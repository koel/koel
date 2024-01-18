import { screen } from '@testing-library/vue'
import { expect, it, Mock } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { authService } from '@/services'
import LoginFrom from './LoginForm.vue'

new class extends UnitTestCase {
  private async submitForm (loginMock: Mock) {
    const rendered = this.render(LoginFrom)

    await this.type(screen.getByPlaceholderText('Email Address'), 'john@doe.com')
    await this.type(screen.getByPlaceholderText('Password'), 'secret')
    await this.user.click(screen.getByRole('button', { name: 'Log In' }))

    expect(loginMock).toHaveBeenCalledWith('john@doe.com', 'secret')

    return rendered
  }

  protected test () {
    it('renders', () => expect(this.render(LoginFrom).html()).toMatchSnapshot())

    it('logs in', async () => {
      expect((await this.submitForm(this.mock(authService, 'login'))).emitted().loggedin).toBeTruthy()
    })

    it('fails to log in', async () => {
      const mock = this.mock(authService, 'login').mockRejectedValue(new Error('Unauthenticated'))
      const { emitted } = await this.submitForm(mock)
      await this.tick()

      expect(emitted().loggedin).toBeFalsy()
      expect(screen.getByTestId('login-form').classList.contains('error')).toBe(true)
    })
  }
}
