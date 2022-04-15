import Component from '@/components/auth/login-form.vue'
import { userStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { shallow } from '@/__tests__/adapter'

describe('components/auth/login-form', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('renders properly', () => {
    expect(shallow(Component)).toMatchSnapshot()
  })

  it('triggers login when form is submitted', () => {
    const loginStub = mock(userStore, 'login')
    shallow(Component, {
      data: () => ({
        email: 'john@doe.com',
        password: 'secret'
      })
    }).submit('form')
    expect(loginStub).toHaveBeenCalledWith('john@doe.com', 'secret')
  })
})
