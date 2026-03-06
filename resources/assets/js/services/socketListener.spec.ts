import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'

const { mockListen } = vi.hoisted(() => {
  const mockListen = vi.fn()
  // socketService.listen returns `this` for chaining
  mockListen.mockReturnValue({ listen: mockListen })
  return { mockListen }
})

vi.mock('@/services/socketService', () => ({
  socketService: {
    listen: mockListen,
    broadcast: vi.fn(),
  },
}))

vi.mock('@/services/playbackManager', () => ({
  playback: vi.fn().mockReturnValue(null),
}))

import { socketListener } from './socketListener'

describe('socketListener', () => {
  createHarness()

  it('registers socket event listeners', () => {
    socketListener.listen()

    expect(mockListen).toHaveBeenCalledWith('SOCKET_TOGGLE_PLAYBACK', expect.any(Function))
    expect(mockListen).toHaveBeenCalledWith('SOCKET_PLAY_NEXT', expect.any(Function))
    expect(mockListen).toHaveBeenCalledWith('SOCKET_PLAY_PREV', expect.any(Function))
    expect(mockListen).toHaveBeenCalledWith('SOCKET_GET_STATUS', expect.any(Function))
    expect(mockListen).toHaveBeenCalledWith('SOCKET_GET_CURRENT_PLAYABLE', expect.any(Function))
    expect(mockListen).toHaveBeenCalledWith('SOCKET_SET_VOLUME', expect.any(Function))
    expect(mockListen).toHaveBeenCalledWith('SOCKET_TOGGLE_FAVORITE', expect.any(Function))
  })
})
