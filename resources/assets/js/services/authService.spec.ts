import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import type { UpdateCurrentProfileData } from '@/services/authService'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { userStore } from '@/stores/userStore'

const originalLocation = window.location

describe('authService', () => {
  const h = createHarness({
    beforeEach: () => {
      Object.defineProperty(window, 'location', {
        value: {
          ...window.location, // eslint-disable-line typescript-eslint/no-misused-spread -- intentional shallow copy for test
        },
        writable: true,
      })
    },
    afterEach: () => {
      // @ts-ignore
      window.location = originalLocation
      useLocalStorage(false).remove('redirect')
    },
  })

  const { get: lsGet, set: lsSet } = useLocalStorage(false)

  it('gets the token', () => {
    lsSet('api-token', 'foo')
    expect(authService.getApiToken()).toBe('foo')
  })

  it.each([
    ['foo', true],
    [null, false],
  ])('checks if the token exists', (token, exists) => {
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
    const postMock = h.mock(http, 'post').mockResolvedValue({
      'audio-token': 'foo',
      token: 'bar',
    })

    const result = await authService.login('john@doe.com', 'curry-wurst')

    expect(postMock).toHaveBeenCalledWith('me', { email: 'john@doe.com', password: 'curry-wurst' })
    expect(result).toBeNull()
    expect(lsGet('api-token')).toBe('bar')
    expect(lsGet('audio-token')).toBe('foo')
  })

  it('returns the two-factor challenge payload from login without stashing a token', async () => {
    authService.destroy()
    h.mock(http, 'post').mockResolvedValue({
      two_factor: true,
      login_token: 'pending-login-token',
    })

    const result = await authService.login('john@doe.com', 'curry-wurst')

    expect(result).toEqual({ two_factor: true, login_token: 'pending-login-token' })
    expect(lsGet('api-token')).toBeNull()
    expect(lsGet('audio-token')).toBeNull()
  })

  it('submits the two-factor challenge and stashes the returned composite token', async () => {
    const postMock = h.mock(http, 'post').mockResolvedValue({
      'audio-token': 'foo',
      token: 'bar',
    })

    await authService.submitTwoFactorChallenge('pending-login-token', '123456')

    expect(postMock).toHaveBeenCalledWith('me/two-factor-challenge', {
      login_token: 'pending-login-token',
      code: '123456',
    })
    expect(lsGet('api-token')).toBe('bar')
    expect(lsGet('audio-token')).toBe('foo')
  })

  it('enrolls in two-factor authentication', async () => {
    const postMock = h.mock(http, 'post').mockResolvedValue({ provisioning_uri: 'otpauth://totp/...' })

    const result = await authService.enrollTwoFactor()

    expect(postMock).toHaveBeenCalledWith('me/two-factor')
    expect(result).toEqual({ provisioning_uri: 'otpauth://totp/...' })
  })

  it('confirms two-factor enrollment and returns the recovery codes', async () => {
    const postMock = h.mock(http, 'post').mockResolvedValue({ recovery_codes: ['AAAA BBBB', 'CCCC DDDD'] })

    const result = await authService.confirmTwoFactor('123456')

    expect(postMock).toHaveBeenCalledWith('me/two-factor/confirm', { code: '123456' })
    expect(result).toEqual({ recovery_codes: ['AAAA BBBB', 'CCCC DDDD'] })
  })

  it('disables two-factor authentication', async () => {
    const deleteMock = h.mock(http, 'delete')

    await authService.disableTwoFactor('654321')

    expect(deleteMock).toHaveBeenCalledWith('me/two-factor', { code: '654321' })
  })

  it('regenerates two-factor recovery codes', async () => {
    const postMock = h.mock(http, 'post').mockResolvedValue({ recovery_codes: ['NEW1 NEW2', 'NEW3 NEW4'] })

    const result = await authService.regenerateRecoveryCodes('123456')

    expect(postMock).toHaveBeenCalledWith('me/two-factor/recovery-codes', { code: '123456' })
    expect(result).toEqual({ recovery_codes: ['NEW1 NEW2', 'NEW3 NEW4'] })
  })

  it('changes the password', async () => {
    const putMock = h.mock(http, 'put')

    await authService.changePassword('old-secret', 'new-secret-1234')

    expect(putMock).toHaveBeenCalledWith('me/password', {
      current_password: 'old-secret',
      new_password: 'new-secret-1234',
    })
  })

  it('redirects after login', async () => {
    const redirectMock = h.mock(authService, 'maybeRedirect')
    lsSet('redirect', 'http://localhost:3000/foo/bar')

    h.mock(http, 'post').mockResolvedValue({
      'audio-token': 'foo',
      token: 'bar',
    })

    await authService.login('john@doe.com', 'curry-wurst')

    expect(redirectMock).toHaveBeenCalled()
  })

  it('logs out', async () => {
    const deleteMock = h.mock(http, 'delete')
    await authService.logout()

    expect(deleteMock).toHaveBeenCalledWith('me')
  })

  it('gets profile', async () => {
    const getMock = h.mock(http, 'get')
    await authService.getProfile()

    expect(getMock).toHaveBeenCalledWith('me')
  })

  it('updates profile', async () => {
    userStore.state.current = h.factory('user').make({
      name: 'John Doe',
      email: 'john@doe.com',
    }) as CurrentUser

    const updated = h.factory('user').make({
      name: 'Jane Doe',
      email: 'jane@doe.com',
    })

    const putMock = h.mock(http, 'put').mockResolvedValue(updated)

    const data: UpdateCurrentProfileData = {
      name: 'Jane Doe',
      email: 'jane@doe.com',
    }

    await authService.updateProfile(data)

    expect(putMock).toHaveBeenCalledWith('me', data)
    expect(userStore.current.name).toBe('Jane Doe')
    expect(userStore.current.email).toBe('jane@doe.com')
  })

  it('sets redirect url', () => {
    authService.setRedirect('/foo/bar')
    expect(lsGet('redirect')).toBe('/foo/bar')
  })

  it('sets redirect url to the current URL', () => {
    h.mock(location, 'toString').mockReturnValue('http://localhost:3000/foo/bar')
    authService.setRedirect()
    expect(lsGet('redirect')).toBe('http://localhost:3000/foo/bar')
  })

  it('checks if redirect url exists', () => {
    lsSet('redirect', 'http://localhost:3000/foo/bar')
    expect(authService.hasRedirect()).toBe(true)
  })

  it('redirects to the stored URL', () => {
    const assignMock = h.mock(location, 'assign')
    lsSet('redirect', 'http://localhost:3000/foo/bar')

    authService.maybeRedirect()

    expect(assignMock).toHaveBeenCalledWith('http://localhost:3000/foo/bar')
    expect(lsGet('redirect')).toBeNull()
  })

  it('does not redirect if no redirect url is stored', () => {
    const assignMock = h.mock(location, 'assign')
    expect(lsGet('redirect')).toBeNull()

    authService.maybeRedirect()

    expect(assignMock).not.toHaveBeenCalled()
    expect(lsGet('redirect')).toBeNull()
  })
})
