import Component from '@/components/main-wrapper/main-content/users.vue'
import UserItem from '@/components/shared/user-item.vue'
import AddUserForm from '@/components/modals/add-user-form.vue'
import EditUserForm from '@/components/modals/edit-user-form.vue'
import factory from '@/tests/factory'
import { userStore } from '@/stores'

describe('components/main-wrapper/main-content/users', () => {
  it('displays the users', () => {
    userStore.all = factory('user', 10)
    mount(Component).findAll(UserItem).should.have.lengthOf(10)
  })

  it('adds new user', () => {
    userStore.all = factory('user', 10)
    const wrapper = mount(Component)
    const openStub = sinon.stub(wrapper.vm.$refs.addUserForm, 'open')
    wrapper.has(AddUserForm).should.be.true
    wrapper.click('.btn-add')
    openStub.called.should.be.true
    openStub.restore()
  })

  it('edits a user', () => {
    userStore.all = factory('user', 10)
    const wrapper = mount(Component)
    const editStub = sinon.stub(wrapper.vm.$refs.editUserForm, 'open')
    wrapper.has(EditUserForm).should.be.true
    wrapper.click('.btn-edit')
    editStub.calledWith(userStore.all[0]).should.be.true
    editStub.restore()
  })
})

