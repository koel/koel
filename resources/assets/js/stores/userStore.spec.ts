import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { http } from '@/services/http'
import type { CreateUserData, UpdateUserData } from '@/stores/userStore'
import { userStore } from '@/stores/userStore'

const currentUser = factory('user', {
  name: 'John Doe',
  email: 'john@doe.com',
  is_admin: true,
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
      const user = factory('user')

      expect(userStore.syncWithVault(user)).toEqual([user])
      expect(userStore.vault.size).toBe(2)
      expect(userStore.vault.get(user.id)).toEqual(user)
    })

    it('fetches users', async () => {
      const users = factory('user', 3)
      const getMock = this.mock(http, 'get').mockResolvedValue(users)

      await userStore.fetch()

      expect(getMock).toHaveBeenCalledWith('users')
      expect(userStore.vault.size).toBe(4)
    })

    it('gets user by id', () => {
      const user = factory('user')
      userStore.syncWithVault(user)

      expect(userStore.byId(user.id)).toEqual(user)
    })

    it('creates a user', async () => {
      const data: CreateUserData = {
        is_admin: false,
        password: 'bratwurst',
        name: 'Jane Doe',
        email: 'jane@doe.com',
      }

      const user = factory('user', data)
      const postMock = this.mock(http, 'post').mockResolvedValue(user)

      expect(await userStore.store(data)).toEqual(user)
      expect(postMock).toHaveBeenCalledWith('users', data)
      expect(userStore.vault.size).toBe(2)
      expect(userStore.state.users).toHaveLength(2)
    })

    it('updates a user', async () => {
      const user = factory('user')
      userStore.state.users.push(...userStore.syncWithVault(user))

      const data: UpdateUserData = {
        is_admin: true,
        password: 'bratwurst',
        name: 'Jane Doe',
        email: 'jane@doe.com',
      }

      const updated = { ...user, ...data }
      const putMock = this.mock(http, 'put').mockResolvedValue(updated)

      await userStore.update(user, data)

      expect(putMock).toHaveBeenCalledWith(`users/${user.id}`, data)
      expect(userStore.vault.get(user.id)).toEqual(updated)
    })

    it('deletes a user', async () => {
      const deleteMock = this.mock(http, 'delete')

      const user = factory('user')
      userStore.state.users.push(...userStore.syncWithVault(user))
      expect(userStore.vault.has(user.id)).toBe(true)

      expect(await userStore.destroy(user))

      expect(deleteMock).toHaveBeenCalledWith(`users/${user.id}`)
      expect(userStore.vault.size).toBe(1)
      expect(userStore.state.users).toHaveLength(1)
      expect(userStore.vault.has(user.id)).toBe(false)
    })
  }
}
