import UnitTestCase from '@/__tests__/UnitTestCase'
import { localStorageService } from '@/services/localStorageService'
import { expect, it } from 'vitest'
import { authService } from './authService'

new class extends UnitTestCase {
  protected test () {
    it('gets the token', () => {
      const mock = this.mock(localStorageService, 'get')
      authService.getToken()
      expect(mock).toHaveBeenCalledWith('api-token')
    })

    it.each([['foo', true], [null, false]])('checks if the token exists', (token, exists) => {
      this.mock(localStorageService, 'get', token)
      expect(authService.hasToken()).toBe(exists)
    })

    it('sets the token', () => {
      const mock = this.mock(localStorageService, 'set')
      authService.setToken('foo')
      expect(mock).toHaveBeenCalledWith('api-token', 'foo')
    })

    it('destroys the token', () => {
      const mock = this.mock(localStorageService, 'remove')
      authService.destroy()
      expect(mock).toHaveBeenCalledWith('api-token')
    })
  }
}
