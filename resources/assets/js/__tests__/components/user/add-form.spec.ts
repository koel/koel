import Component from '@/components/user/UserAddForm.vue'
import { userStore } from '@/stores'
import { mock } from '@/__tests__/__helpers__'
import { shallow, mount } from '@/__tests__/adapter'

describe('components/user/UserAddForm', () => {
  afterEach(() => {
    jest.resetModules()
    jest.clearAllMocks()
  })

  it('adds a new user', () => {
    const storeStub = mock(userStore, 'store')
    const wrapper = shallow(Component)
    wrapper.find('[name=name]').setValue('Super User').input()
    wrapper.find('[name=email]').setValue('su@koel.dev').input()
    wrapper.find('[name=password]').setValue('VerySecure').input()
    wrapper.submit('form.user-add')

    expect(storeStub).toHaveBeenCalledWith({
      name: 'Super User',
      email: 'su@koel.dev',
      password: 'VerySecure',
      is_admin: false
    })
  })

  it('cancels', async () => {
    const wrapper = mount(Component)

    await wrapper.vm.$nextTick()
    wrapper.click('.btn-cancel')
    expect(wrapper.hasEmitted('close')).toBe(true)
  })
})
