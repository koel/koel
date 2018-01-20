import Profile from '@/components/main-wrapper/main-content/profile.vue'
import factory from '@/tests/factory'
import { userStore, preferenceStore } from '@/stores'

describe('components/main-wrapper/main-content/user', () => {
  beforeEach(() => {
    userStore.state.current = factory('user')
  })

  it('displays a form to update profile', () => {
    shallow(Profile).has('form').should.be.true
  })

  it('validates password confirmation', () => {
    const wrapper = shallow(Profile, { data: {
      pwd: 'foo',
      confirmPwd: 'bar'
    }})
    const updateProfileStub = sinon.stub(userStore, 'updateProfile')
    wrapper.submit('form')
    updateProfileStub.called.should.be.false
    wrapper.setData({
      confirmPwd: 'foo'
    })
    wrapper.submit('form')
    updateProfileStub.called.should.be.true
    updateProfileStub.restore()
  })

  it('updates profile with password fields left empty', () => {
    const wrapper = shallow(Profile)
    const updateProfileStub = sinon.stub(userStore, 'updateProfile')
    wrapper.submit('form')
    updateProfileStub.called.should.be.true
    updateProfileStub.restore()
  })

  it('updates preferences', () => {
    const wrapper = shallow(Profile)
    const savePrefsStub = sinon.spy(preferenceStore, 'save')
    wrapper.setData({ prefs: preferenceStore.state })
    ;['notify', 'confirmClosing', 'transcodeOnMobile'].forEach(key => {
      wrapper.submit(`input[name=${key}]`)
      savePrefsStub.called.should.be.true
    })
    savePrefsStub.restore()
  })
})
