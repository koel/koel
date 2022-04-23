import Component from '@/components/user/UserEditForm.vue'
import { userStore } from '@/stores'
import factory from '@/__tests__/factory'
import { mock } from '@/__tests__/__helpers__'
import { shallow, mount } from '@/__tests__/adapter'

describe('components/user/UserEditForm', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('saves', () => {
    const user = factory<User>('user')
    const updateMock = mock(userStore, 'update')
    const wrapper = shallow(Component, {
      propsData: {
        user
      }
    })

    wrapper.find('[name=name]').setValue('Super User').input()
    wrapper.find('[name=email]').setValue('su@koel.dev').input()
    wrapper.find('[name=password]').setValue('SuperSecure').input()
    wrapper.submit('form')

    expect(updateMock).toHaveBeenCalledWith(user, {
      name: 'Super User',
      email: 'su@koel.dev',
      password: 'SuperSecure',
      is_admin: false
    })
  })

  it('cancels', async () => {
    const user = factory('user')
    const updateMock = mock(userStore, 'update')
    const wrapper = mount(Component, {
      propsData: {
        user
      }
    })

    await wrapper.vm.$nextTick()
    wrapper.click('.btn-cancel')
    expect(wrapper.hasEmitted('close')).toBe(true)
    expect(updateMock).not.toHaveBeenCalled()
  })
})
