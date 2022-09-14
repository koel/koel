import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { httpService } from '@/services'
import { CreateUserData, UpdateCurrentProfileData, UpdateUserData, userStore } from '.'

const currentUser = factory<User>('user', {
  id: 1,
  name: 'John Doe',
  email: 'john@doe.com',
  is_admin: true
})

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      userStore.vault.clear()
      userStore.init(currentUser)
    })
  }

  protected test () {
    it('initializes with current user', () => {
      expect(userStore.current).toEqual(currentUser)
      expect(userStore.vault.size).toBe(1)
    })

    it('syncs with vault', () => {
      const user = factory<User>('user')

      expect(userStore.syncWithVault(user)).toEqual([user])
      expect(userStore.vault.size).toBe(2)
      expect(userStore.vault.get(user.id)).toEqual(user)
    })

    it('fetches users', async () => {
      const users = factory<User>('user', 3)
      const getMock = this.mock(httpService, 'get').mockResolvedValue(users)

      await userStore.fetch()

      expect(getMock).toHaveBeenCalledWith('users')
      expect(userStore.vault.size).toBe(4)
    })

    it('gets user by id', () => {
      const user = factory<User>('user', { id: 2 })
      userStore.syncWithVault(user)

      expect(userStore.byId(2)).toEqual(user)
    })

    it('logs in', async () => {
      const postMock = this.mock(httpService, 'post')
      await userStore.login('john@doe.com', 'curry-wurst')

      expect(postMock).toHaveBeenCalledWith('me', { email: 'john@doe.com', password: 'curry-wurst' })
    })

    it('logs out', async () => {
      const deleteMock = this.mock(httpService, 'delete')
      await userStore.logout()

      expect(deleteMock).toHaveBeenCalledWith('me')
    })

    it('gets profile', async () => {
      const getMock = this.mock(httpService, 'get')
      await userStore.getProfile()

      expect(getMock).toHaveBeenCalledWith('me')
    })

    it('updates profile', async () => {
      const updated = factory<User>('user', {
        id: 1,
        name: 'Jane Doe',
        email: 'jane@doe.com'
      })

      const putMock = this.mock(httpService, 'put').mockResolvedValue(updated)

      const data: UpdateCurrentProfileData = {
        current_password: 'curry-wurst',
        name: 'Jane Doe',
        email: 'jane@doe.com'
      }

      await userStore.updateProfile(data)

      expect(putMock).toHaveBeenCalledWith('me', data)
      expect(userStore.current.name).toBe('Jane Doe')
      expect(userStore.current.email).toBe('jane@doe.com')
    })

    it('creates a user', async () => {
      const data: CreateUserData = {
        is_admin: false,
        password: 'bratwurst',
        name: 'Jane Doe',
        email: 'jane@doe.com'
      }

      const user = factory<User>('user', data)
      const postMock = this.mock(httpService, 'post').mockResolvedValue(user)

      expect(await userStore.store(data)).toEqual(user)
      expect(postMock).toHaveBeenCalledWith('users', data)
      expect(userStore.vault.size).toBe(2)
      expect(userStore.state.users).toHaveLength(2)
    })

    it('updates a user', async () => {
      const user = factory<User>('user', { id: 2 })
      userStore.state.users.push(...userStore.syncWithVault(user))

      const data: UpdateUserData = {
        is_admin: true,
        password: 'bratwurst',
        name: 'Jane Doe',
        email: 'jane@doe.com'
      }

      const updated = { ...user, ...data }
      const putMock = this.mock(httpService, 'put').mockResolvedValue(updated)

      await userStore.update(user, data)

      expect(putMock).toHaveBeenCalledWith('users/2', data)
      expect(userStore.vault.get(2)).toEqual(updated)
    })

    it('deletes a user', async () => {
      const deleteMock = this.mock(httpService, 'delete')

      const user = factory<User>('user', { id: 2 })
      userStore.state.users.push(...userStore.syncWithVault(user))
      expect(userStore.vault.has(2)).toBe(true)

      expect(await userStore.destroy(user))

      expect(deleteMock).toHaveBeenCalledWith('users/2')
      expect(userStore.vault.size).toBe(1)
      expect(userStore.state.users).toHaveLength(1)
      expect(userStore.vault.has(2)).toBe(false)
    })
  }
}
