import LoginForm from '@/components/auth/login-form.vue'

describe('components/auth/login-form', () => {
  it('displays a form for users to log in', () => {
    shallow(LoginForm).findAll('form').should.have.lengthOf(1)
  })

  it('triggers login method when form is submitted', () => {
    const spy = sinon.spy()
    const wrapper = shallow(LoginForm)
    wrapper.vm.login = spy
    wrapper.find('form').trigger('submit')
    spy.called.should.be.true
  })
})
