import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import { authService, http, UpdateCurrentProfileData } from '@/services'
import { useLocalStorage } from '@/composables'
import factory from '@/__tests__/factory'
import { userStore } from '@/stores'

new class extends UnitTestCase {
  protected test () {
    const { get: lsGet, set: lsSet } = useLocalStorage(false)

    it('gets the token', () => {
      lsSet('api-token', 'foo')
      expect(authService.getApiToken()).toBe('foo')
    })

    it.each([['foo', true], [null, false]])('checks if the token exists', (token, exists) => {
      lsSet('api-token', token)
      expect(authService.hasApiToken()).toBe(exists)
    })

    it('sets the token', () => {
      authService.setApiToken('foo')
      expect(lsGet('api-token')).toBe('foo')
    })

    it('destroys the token', () => {
      lsSet('api-token', 'foo')
      authService.destroy()
      expect(lsGet('api-token')).toBeNull()
    })

    it('logs in', async () => {
      const postMock = this.mock(http, 'post').mockResolvedValue({
        'audio-token': 'foo',
        token: 'bar'
      })

      await authService.login('john@doe.com', 'curry-wurst')

      expect(postMock).toHaveBeenCalledWith('me', { email: 'john@doe.com', password: 'curry-wurst' })
    })

    it('logs out', async () => {
      const deleteMock = this.mock(http, 'delete')
      await authService.logout()

      expect(deleteMock).toHaveBeenCalledWith('me')
    })

    it('gets profile', async () => {
      const getMock = this.mock(http, 'get')
      await authService.getProfile()

      expect(getMock).toHaveBeenCalledWith('me')
    })

    it('updates profile', async () => {
      userStore.state.current = factory('user', {
        id: 1,
        name: 'John Doe',
        email: 'john@doe.com'
      })

      const updated = factory('user', {
        id: 1,
        name: 'Jane Doe',
        email: 'jane@doe.com'
      })

      const putMock = this.mock(http, 'put').mockResolvedValue(updated)

      const data: UpdateCurrentProfileData = {
        current_password: 'curry-wurst',
        name: 'Jane Doe',
        email: 'jane@doe.com'
      }

      await authService.updateProfile(data)

      expect(putMock).toHaveBeenCalledWith('me', data)
      expect(userStore.current.name).toBe('Jane Doe')
      expect(userStore.current.email).toBe('jane@doe.com')
    })
  }
}
