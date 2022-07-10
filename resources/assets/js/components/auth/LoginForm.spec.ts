import { fireEvent } from '@testing-library/vue'
import { expect, it, SpyInstanceFn } from 'vitest'
import { userStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import LoginFrom from './LoginForm.vue'

new class extends UnitTestCase {
  private async submitForm (loginMock: SpyInstanceFn) {
    const rendered = this.render(LoginFrom)

    await fireEvent.update(rendered.getByPlaceholderText('Email Address'), 'john@doe.com')
    await fireEvent.update(rendered.getByPlaceholderText('Password'), 'secret')
    await fireEvent.submit(rendered.getByTestId('login-form'))

    expect(loginMock).toHaveBeenCalledWith('john@doe.com', 'secret')

    return rendered
  }

  protected test () {
    it('renders', () => expect(this.render(LoginFrom).html()).toMatchSnapshot())

    it('logs in', async () => {
      expect((await this.submitForm(this.mock(userStore, 'login'))).emitted().loggedin).toBeTruthy()
    })

    it('fails to log in', async () => {
      const mock = this.mock(userStore, 'login').mockRejectedValue(new Error('Unauthenticated'))
      const { getByTestId, emitted } = await this.submitForm(mock)
      await this.tick()

      expect(emitted().loggedin).toBeFalsy()
      expect(getByTestId('login-form').classList.contains('error')).toBe(true)
    })
  }
}
