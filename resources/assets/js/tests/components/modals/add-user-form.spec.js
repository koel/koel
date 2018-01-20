import Component from '@/components/modals/add-user-form.vue'
import factory from '@/tests/factory'
import { userStore } from '@/stores'

describe('components/modals/add-user-form', () => {
  it('opens', async done => {
    const wrapper = shallow(Component)
    await wrapper.vm.open()
    wrapper.has('form.user-add').should.be.true
    done()
  })

  it('adds a new user', async done => {
    const newUser = factory('user')
    const storeStub = sinon.stub(userStore, 'store')
    const wrapper = shallow(Component)
    await wrapper.vm.open()
    wrapper.setData({ newUser })
    wrapper.submit('form.user-add')
    storeStub.calledWith(newUser.name, newUser.email, newUser.password).should.be.true
    storeStub.restore()
    done()
  })

  it('cancels', async done => {
    const wrapper = shallow(Component)
    await wrapper.vm.open()
    wrapper.has('form.user-add').should.be.true
    await wrapper.vm.cancel()
    wrapper.has('form.user-add').should.be.false
    done()
  })
})
