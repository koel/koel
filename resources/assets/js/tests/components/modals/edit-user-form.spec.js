import Component from '@/components/modals/edit-user-form.vue'
import { userStore } from '@/stores'
import factory from '@/tests/factory'

describe('components/modals/edit-user-form', () => {
  it('opens', async done => {
    const user = factory('user')
    const wrapper = shallow(Component)
    await wrapper.vm.open(user)
    wrapper.has('form.user-edit').should.be.true
    wrapper.find('input[name=name]').value.should.equal(user.name)
    wrapper.find('input[name=email]').value.should.equal(user.email)
    done()
  })

  it('saves', async done => {
    const user = factory('user')
    const updateStub = sinon.stub(userStore, 'update')
    const wrapper = shallow(Component)
    await wrapper.vm.open(user)
    wrapper.submit('form')
    updateStub.calledWith(user, user.name, user.email, user.password).should.be.true
    updateStub.restore()
    done()
  })

  it('cancels', async done => {
    const user = factory('user')
    const updateStub = sinon.stub(userStore, 'update')
    const wrapper = shallow(Component)
    await wrapper.vm.open(user)
    wrapper.has('form.user-edit').should.be.true
    wrapper.click('.btn-cancel')
    wrapper.has('form.user-edit').should.be.false
    updateStub.called.should.be.false
    updateStub.restore()
    done()
  })
})
