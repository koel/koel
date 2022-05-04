import { mockHelper, render } from '@/__tests__/__helpers__'
import { cleanup, fireEvent } from '@testing-library/vue'
import { beforeEach, expect, it } from 'vitest'
import { userStore } from '@/stores'
import LoginFrom from './LoginForm.vue'
import Btn from '@/components/ui/Btn.vue'

beforeEach(() => {
  mockHelper.restoreAllMocks()
  cleanup()
})

it('renders', () => expect(render(LoginFrom, {
  global: {
    stubs: {
      Btn
    }
  }
}).html()).toMatchSnapshot())

it('triggers login when submitted', async () => {
  const mock = mockHelper.mock(userStore, 'login')

  const { getByTestId, getByPlaceholderText } = render(LoginFrom, {
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