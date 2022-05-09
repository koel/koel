import { fireEvent } from '@testing-library/vue'
import { expect, it } from 'vitest'
import { userStore } from '@/stores'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import LoginFrom from './LoginForm.vue'
import Btn from '@/components/ui/Btn.vue'

new class extends ComponentTestCase {
  protected test () {
    it('renders', () => expect(this.render(LoginFrom, {
      global: {
        stubs: {
          Btn
        }
      }
    }).html()).toMatchSnapshot())

    it('triggers login when submitted', async () => {
      const mock = this.mock(userStore, 'login')

      const { getByTestId, getByPlaceholderText } = this.render(LoginFrom, {
        global: {
          stubs: {
            Btn
          }
        }
      })

      await fireEvent.update(getByPlaceholderText('Email Address'), 'john@doe.com')
      await fireEvent.update(getByPlaceholderText('Password'), 'secret')
      await fireEvent.submit(getByTestId('login-form'))

      expect(mock).toHaveBeenCalledWith('john@doe.com', 'secret')
    })
  }
}
