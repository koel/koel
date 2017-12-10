import LoginForm from '@/components/auth/login-form.vue'

describe('components/auth/login-form', () => {
  it('displays a form for users to log in', () => {
    const wrapper = shallow(LoginForm)
    expect(wrapper.findAll('form')).toHaveLength(1)
  })

  it('triggers login method when form is submitted', () => {
    const spy = sinon.spy()
    const wrapper = shallow(LoginForm)
    wrapper.vm.login = spy
    wrapper.find('form').trigger('submit')
    expect(spy.called).toBe(true)
  })
})
