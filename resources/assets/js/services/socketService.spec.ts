import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'

const { mockPusherInstance, mockChannel } = vi.hoisted(() => {
  const mockChannel = {
    trigger: vi.fn(),
    bind: vi.fn(),
  }

  const mockPusherInstance = {
    subscribe: vi.fn().mockReturnValue(mockChannel),
  }

  return { mockPusherInstance, mockChannel }
})

vi.mock('pusher-js', () => ({
  default: vi.fn().mockImplementation(function () {
    return mockPusherInstance
  }),
}))

vi.mock('@/services/authService', () => ({
  authService: {
    getApiToken: vi.fn().mockReturnValue('test-token'),
  },
}))

import { socketService } from './socketService'
import { userStore } from '@/stores/userStore'

describe('socketService', () => {
  const h = createHarness()

  it('does not init without PUSHER_APP_KEY', async () => {
    Object.defineProperty(window, 'PUSHER_APP_KEY', { value: '', writable: true, configurable: true })
    const result = await socketService.init()
    expect(result).toBe(false)
  })

  it('inits with PUSHER_APP_KEY', async () => {
    Object.defineProperty(window, 'PUSHER_APP_KEY', { value: 'test-key', writable: true, configurable: true })
    Object.defineProperty(window, 'PUSHER_APP_CLUSTER', { value: 'mt1', writable: true, configurable: true })
    window.BASE_URL = 'http://localhost/'

    const result = await socketService.init()
    expect(result).toBe(true)
    expect(mockPusherInstance.subscribe).toHaveBeenCalledWith('private-koel')
  })

  it('broadcasts events', () => {
    const user = h.factory('user') as CurrentUser
    userStore.state.current = user

    socketService.broadcast('TEST_EVENT', { foo: 'bar' })
    expect(mockChannel.trigger).toHaveBeenCalledWith(`client-TEST_EVENT.${user.id}`, { foo: 'bar' })
  })

  it('listens to events', () => {
    const user = h.factory('user') as CurrentUser
    userStore.state.current = user

    const cb = vi.fn()
    socketService.listen('TEST_EVENT', cb)
    expect(mockChannel.bind).toHaveBeenCalledWith(`client-TEST_EVENT.${user.id}`, expect.any(Function))
  })

  it('returns itself for chaining', () => {
    expect(socketService.broadcast('E')).toBe(socketService)
    expect(socketService.listen('E', vi.fn())).toBe(socketService)
  })
})
