import type { Broadcaster } from 'laravel-echo'
import Echo from 'laravel-echo'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { uploadService } from '@/services/uploadService'
import { broadcastSubscriber } from '@/services/broadcastSubscriber'

/**
 * For testing purposes, we create a fake Echo instance that extends the Echo<'null'> class,
 * which allows us to simulate the behavior of a private channel without needing a real server.
 * The Echo class will take in fake channels, allowing testing the event listening functionality.
 */
class FakeEcho extends Echo<'null'> {
  public channels: Record<string, FakeChannel> = {}

  private(channelName: string) {
    const channel = new FakeChannel()
    this.channels[channelName] = channel
    return channel as unknown as Broadcaster['null']['private']
  }
}

class FakeChannel {
  private listeners: Record<string, Closure> = {}

  listen(event: string, callback: Closure) {
    this.listeners[event] = callback
    return this
  }

  trigger(event: string, data: any) {
    this.listeners[event]?.(data)
  }
}

describe('broadcastSubscriber', () => {
  const h = createHarness()

  const echo = new FakeEcho({
    broadcaster: 'null',
  })

  broadcastSubscriber.init('foo-id', echo)
  const privateChannel = echo.channels['user.foo-id']
  expect(privateChannel).toBeInstanceOf(FakeChannel)

  it('listens to .song.uploaded event', () => {
    const handleMock = h.mock(uploadService, 'handleUploadResult')
    privateChannel.trigger('.song.uploaded', 'some data')
    expect(handleMock).toHaveBeenCalledWith('some data')
  })

  it('connects to Pusher with the credentials the server rendered', async () => {
    window.KOEL.pusher = { app_key: 'the-key', app_cluster: 'eu' }

    const instance = await broadcastSubscriber.newEchoInstance()

    expect(instance.options.broadcaster).toBe('pusher')
    expect(instance.options.key).toBe('the-key')
    expect(instance.options.cluster).toBe('eu')
    expect(instance.options.authEndpoint).toBe(`${window.KOEL.base_url}api/broadcasting/auth`)
  })

  it('falls back to no broadcaster when the server rendered no credentials', async () => {
    window.KOEL.pusher = { app_key: '', app_cluster: '' }

    const instance = await broadcastSubscriber.newEchoInstance()

    expect(instance.options.broadcaster).toBe('null')
  })
})
