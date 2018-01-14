import Component from '@/components/shared/user-item.vue'
import { userStore } from '@/stores'
import { alerts } from '@/utils'
import factory from '@/tests/factory'
import router from '@/router'

describe('components/shared/user-item', () => {
  let user
  
  beforeEach(() => {
    user = factory('user')
    // fake the user avatar
    user.avatar = 'http://foo.bar/baz.jpg'
    // make sure the user is not current logged in user
    userStore.current.id = user.id + 1
  })

  it('renders properly', () => {
    const wrapper = shallow(Component, { propsData: { user }})
    const html = wrapper.html()
    html.should.contain(user.email)
    html.should.contain(user.avatar)
    html.should.contain(user.name)
    wrapper.find('.btn-edit').text().should.equal('Edit')
    wrapper.contains('.btn-delete').should.be.true
  })

  it('has different behaviors if current user', () => {
    userStore.current.id = user.id
    const wrapper = shallow(Component, { propsData: { user }})
    wrapper.contains('.btn-delete').should.be.false
    wrapper.find('.btn-edit').text().should.equal('Update Profile')
  })

  it('redirects to update profile if attempt to edit current user', () => {
    const goStub = sinon.stub(router, 'go')
    userStore.current.id = user.id
    shallow(Component, { propsData: { user }}).find('.btn-edit').trigger('click')
    goStub.calledWith('profile').should.be.true
    goStub.restore()
  })

  it('edits user', () => {
    const wrapper = shallow(Component, { propsData: { user }})
    wrapper.find('.btn-edit').trigger('click')
    wrapper.emitted().editUser[0].should.eql([user])
  })

  it('triggers deleting user', () => {
    const confirmStub = sinon.stub(alerts, 'confirm')
    const wrapper = shallow(Component, { propsData: { user }})
    wrapper.find('.btn-delete').trigger('click')
    confirmStub.calledWith(
      `You’re about to unperson ${user.name}. Are you sure?`,
      wrapper.vm.doDelete
    ).should.be.true
  })

  it('deletes user', () => {
    const destroyStub = sinon.stub(userStore, 'destroy')
    shallow(Component, { propsData: { user }}).vm.doDelete()
    destroyStub.calledWith(user).should.be.true
  })
})
