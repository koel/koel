import { describe, expect, it, vi } from 'vitest'
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

    expect(requestMock).toHaveBeenCalledWith('post', 'endpoint', { key: 'value' }, undefined)
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

  it('emits LOG_OUT on 401 for non-login requests', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    h.mock(authService, 'setRedirect')

    const error = {
      response: { status: 401 },
      config: { method: 'get', url: 'songs' },
    }

    // Access the response error interceptor
    const errorInterceptor = (http.client.interceptors.response as any).handlers[0].rejected

    await expect(errorInterceptor(error)).rejects.toBe(error)
    expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
  })

  it('does not emit LOG_OUT on 401 for login request', async () => {
    const emitMock = h.mock(eventBus, 'emit')

    const error = {
      response: { status: 401 },
      config: { method: 'post', url: 'me' },
    }

    const errorInterceptor = (http.client.interceptors.response as any).handlers[0].rejected

    await expect(errorInterceptor(error)).rejects.toBe(error)
    expect(emitMock).not.toHaveBeenCalledWith('LOG_OUT')
  })

  it('emits LOG_OUT on 400 for non-login requests', async () => {
    const emitMock = h.mock(eventBus, 'emit')
    h.mock(authService, 'setRedirect')

    const error = {
      response: { status: 400 },
      config: { method: 'get', url: 'data' },
    }

    const errorInterceptor = (http.client.interceptors.response as any).handlers[0].rejected

    await expect(errorInterceptor(error)).rejects.toBe(error)
    expect(emitMock).toHaveBeenCalledWith('LOG_OUT')
  })

  it('saves token from response header', async () => {
    const setTokenMock = h.mock(authService, 'setApiToken')

    const response = {
      headers: { authorization: 'new-token' },
      data: {},
    }

    const successInterceptor = (http.client.interceptors.response as any).handlers[0].fulfilled

    successInterceptor(response)

    expect(setTokenMock).toHaveBeenCalledWith('new-token')
  })
})
