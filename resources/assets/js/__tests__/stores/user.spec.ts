import { userStore } from '@/stores'
import data from '@/__tests__/blobs/data'

const { users, currentUser } = data

describe('stores/user', () => {
  beforeEach(() => userStore.init(users, currentUser))

  it('sets data state', () => {
    expect(userStore.state.users).toBe(users)
    expect(userStore.state.current).toBe(currentUser)
  })

  it('returns all users', () => {
    expect(userStore.all).toBe(users)
  })

  it('gets a user by ID', () => {
    expect(userStore.byId(1)).toBe(users[0])
  })

  it('gets the current user', () => {
    expect(userStore.current.id).toBe(1)
  })

  it('sets the current user', () => {
    userStore.current = users[1]
    expect(userStore.current.id).toBe(2)
  })

  it('sets the current user’s avatar', () => {
    userStore.setAvatar()
    expect(userStore.current.avatar).toBe('https://www.gravatar.com/avatar/b9611f1bba1aacbe6f5de5856695a202?s=256&d=mp')
  })

  it('sets a user’s avatar', () => {
    userStore.setAvatar(users[1])
    expect(users[1].avatar).toBe('https://www.gravatar.com/avatar/5024672cfe53f113b746e1923e373058?s=256&d=mp')
  })
})
