import LoginForm from '@/components/auth/login-form.vue'
import { userStore } from '@/stores'

describe('components/auth/login-form', () => {
  it('displays a form for users to log in', () => {
    shallow(LoginForm).findAll('form').should.have.lengthOf(1)
  })

  it('triggers login when form is submitted', () => {
    const wrapper = shallow(LoginForm)
    const loginStub = sinon.stub(userStore, 'login')
    wrapper.find('form').trigger('submit')
    loginStub.calledWith(wrapper.vm.email, wrapper.vm.password).should.be.true
    loginStub.restore()
  })
})
