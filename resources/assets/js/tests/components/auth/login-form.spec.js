import LoginForm from '@/components/auth/login-form.vue'

describe('components/auth/login-form', () => {
  it('displays a form for users to log in', () => {
    shallow(LoginForm).findAll('form').should.have.lengthOf(1)
  })

  it('triggers login method when form is submitted', () => {
    const wrapper = shallow(LoginForm)
    const loginStub = sinon.stub()
    wrapper.vm.login = loginStub
    wrapper.find('form').trigger('submit')
    loginStub.called.should.be.true
  })
})
