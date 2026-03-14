import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { eventBus } from '@/utils/eventBus'

vi.mock('nprogress', () => ({
  default: { start: vi.fn(), done: vi.fn() },
}))

describe('http service', () => {
  const h = createHarness()

  it('provides a silently mode that returns self', () => {
    expect(http.silently).toBe(http)
  })

  it('delegates get requests', async () => {
    const requestMock = h.mock(http, 'request').mockResolvedValue({ data: 'result' })

    const result = await http.get('endpoint')

    expect(requestMock).toHaveBeenCalledWith('get', 'endpoint')
    expect(result).toBe('result')
  })

  it('delegates post requests', async () => {
    const requestMock = h.mock(http, 'request').mockResolvedValue({ data: 'result' })

    const result = await http.post('endpoint', { key: 'value' })

    expect(requestMock).toHaveBeenCalledWith('post', 'endpoint', { key: 'value' })
    expect(result).toBe('result')
  })

  it('delegates put requests', async () => {
    const requestMock = h.mock(http, 'request').mockResolvedValue({ data: 'result' })

    await http.put('endpoint', { key: 'value' })

    expect(requestMock).toHaveBeenCalledWith('put', 'endpoint', { key: 'value' })
  })

  it('delegates patch requests', async () => {
    const requestMock = h.mock(http, 'request').mockResolvedValue({ data: 'result' })

    await http.patch('endpoint', { key: 'value' })

    expect(requestMock).toHaveBeenCalledWith('patch', 'endpoint', { key: 'value' })
  })

  it('delegates delete requests', async () => {
    const requestMock = h.mock(http, 'request').mockResolvedValue({ data: 'result' })

    await http.delete('endpoint', { key: 'value' })

    expect(requestMock).toHaveBeenCalledWith('delete', 'endpoint', { key: 'value' })
  })

  describe('interceptor behavior', () => {
    const originalFetch = globalThis.fetch

    afterEach(() => {
      globalThis.fetch = originalFetch
    })

    const mockFetch = (status: number, data: any = {}, headers: Record<string, string> = {}) => {
      globalThis.fetch = vi.fn().mockResolvedValue(
        new Response(JSON.stringify(data), {
          status,
          headers: { 'Content-Type': 'application/json', ...headers },
        }),
      )
    }

    it('emits LOG_OUT on 401 for non-login requests', async () => {
      mockFetch(401, {})
      h.restoreAllMocks()
      const emitMock = h.mock(eventBus, 'emit')
      h.mock(authService, 'setRedirect')

      await expect(http.get('songs')).rejects.toThrow()
      expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
    })

    it('does not emit LOG_OUT on 401 for login request', async () => {
      mockFetch(401, {})
      h.restoreAllMocks()
      const emitMock = h.mock(eventBus, 'emit')

      await expect(http.post('me', {})).rejects.toThrow()
      expect(emitMock).not.toHaveBeenCalledWith('LOG_OUT')
    })

    it('emits LOG_OUT on 400 for non-login requests', async () => {
      mockFetch(400, {})
      h.restoreAllMocks()
      const emitMock = h.mock(eventBus, 'emit')
      h.mock(authService, 'setRedirect')

      await expect(http.get('data')).rejects.toThrow()
      expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
    })

    it('saves token from response header', async () => {
      mockFetch(200, { result: 'ok' }, { authorization: 'new-token' })
      h.restoreAllMocks()
      const setTokenMock = h.mock(authService, 'setApiToken')

      await http.get('endpoint')

      expect(setTokenMock).toHaveBeenCalledWith('new-token')
    })
  })
})
