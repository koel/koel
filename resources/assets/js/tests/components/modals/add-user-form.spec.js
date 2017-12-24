import Component from '@/components/modals/add-user-form.vue'
import factory from '@/tests/factory'
import { userStore } from '@/stores'

describe('components/modals/add-user-form', () => {
  it('renders properly', () => {
    shallow(Component, { data: {
      newUser: factory('user')
    }}).contains('form.user-add').should.be.true
  })

  it('adds a new user', () => {
    const newUser = factory('user')
    const wrapper = shallow(Component, { data: { newUser }})
    const storeStub = sinon.stub(userStore, 'store')
    wrapper.find('form.user-add').trigger('submit')
    storeStub.calledWith(newUser.name, newUser.email, newUser.password).should.be.true
    storeStub.restore()
  })

  it('cancels', () => {
    const newUser = factory('user')
    const wrapper = shallow(Component, { data: { newUser }})
    const storeStub = sinon.stub(userStore, 'store')
    wrapper.findAll('.overlay').should.have.lengthOf(1)
    wrapper.find('form.user-add .btn-cancel').trigger('click')
    wrapper.findAll('.overlay').should.have.lengthOf(0)
  })
})
