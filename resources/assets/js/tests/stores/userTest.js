import { userStore } from '../../stores'
import data from '../blobs/data'

const { users } = data

describe('stores/user', () => {
  beforeEach(() => userStore.init(data.users, data.currentUser))

  describe('#init', () => {
    it('correctly sets data state', () => {
      userStore.state.users.should.equal(data.users)
      userStore.state.current.should.equal(data.currentUser)
    })
  })

  describe('#all', () => {
    it('correctly returns all users', () => {
      userStore.all.should.equal(data.users)
    })
  })

  describe('#byId', () => {
    it('correctly gets a user by ID', () => {
      userStore.byId(1).should.equal(data.users[0])
    })
  })

  describe('#current', () => {
    it('correctly gets the current user', () => {
      userStore.current.id.should.equal(1)
    })

    it('correctly sets the current user', () => {
      userStore.current = data.users[1]
      userStore.current.id.should.equal(2)
    })
  })

  describe('#setAvatar', () => {
    it('correctly sets the current user’s avatar', () => {
      userStore.setAvatar()
      userStore.current.avatar.should.equal('https://www.gravatar.com/avatar/b9611f1bba1aacbe6f5de5856695a202?s=256')
    })

    it('correctly sets a user’s avatar', () => {
      userStore.setAvatar(data.users[1])
      data.users[1].avatar.should.equal('https://www.gravatar.com/avatar/5024672cfe53f113b746e1923e373058?s=256')
    })
  })
})
