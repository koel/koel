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

  describe('retries', () => {
    const mockClient = () => {
      h.restoreAllMocks()

      return h
        .mock(http as any, 'client')
        .mockImplementation(async () => new Response('{}', { headers: { 'content-type': 'application/json' } }))
    }

    it('does not retry by default', async () => {
      const clientMock = mockClient()

      await http.put('queue/state', {})

      expect(clientMock.mock.calls[0][1]).not.toHaveProperty('retry')
    })

    it('retries repeatable requests on temporary server errors', async () => {
      const clientMock = mockClient()

      await http.withRetries.put('queue/state', {})

      expect(clientMock.mock.calls[0][1]).toMatchObject({
        retry: { limit: 3, methods: ['put'], statusCodes: [408, 429, 500, 502, 503, 504] },
      })
    })

    it('retries requests that add something only when the server did not handle them', async () => {
      const clientMock = mockClient()

      await http.withRetriesWhenUnprocessed.post('interaction/play', {})

      expect(clientMock.mock.calls[0][1]).toMatchObject({
        retry: { limit: 3, methods: ['post'], statusCodes: [429, 502, 503] },
      })
    })

    it('sends a repeatable request again after a temporary server error', async () => {
      h.restoreAllMocks()
      const originalFetch = globalThis.fetch
      const fetchMock = vi
        .fn()
        .mockResolvedValueOnce(new Response('{}', { status: 503 }))
        .mockResolvedValueOnce(new Response('{"saved":true}', { headers: { 'content-type': 'application/json' } }))
      globalThis.fetch = fetchMock

      try {
        await expect(http.withRetries.put('queue/state', {})).resolves.toEqual({ saved: true })
        expect(fetchMock).toHaveBeenCalledTimes(2)
      } finally {
        globalThis.fetch = originalFetch
      }
    })

    it('applies a retry option to the next request only', async () => {
      const clientMock = mockClient()

      await http.withRetries.put('queue/state', {})
      await http.put('queue/state', {})

      expect(clientMock.mock.calls[1][1]).not.toHaveProperty('retry')
    })
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

    it.each([
      ['build-b', true],
      ['build-a', false],
    ])('announces a new version when the API reports build %s', async (build, announced) => {
      window.KOEL.build = 'build-a'
      mockFetch(200, { result: 'ok' }, { 'x-koel-build': build })
      h.restoreAllMocks()
      const emitMock = h.mock(eventBus, 'emit')

      await http.get('endpoint')

      expect(emitMock.mock.calls.some(([event]) => event === 'NEW_VERSION_DEPLOYED')).toBe(announced)
      window.KOEL.build = null
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
