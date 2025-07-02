import type { Broadcaster } from 'laravel-echo'
import Echo from 'laravel-echo'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { uploadService } from '@/services/uploadService'
import { broadcastSubscriber } from '@/services/broadcastSubscriber'

/**
 * For testing purposes, we create a fake Echo instance that extends the Echo<'null'> class,
 * which allows us to simulate the behavior of a private channel without needing a real server.
 * The Echo class will take in fake channels, allowing testing the event listening functionality.
 */
class FakeEcho extends Echo<'null'> {
  public channels: Record<string, FakeChannel> = {}

  private (channelName: string) {
    const channel = new FakeChannel()
    this.channels[channelName] = channel
    return channel as unknown as Broadcaster['null']['private']
  }
}

class FakeChannel {
  private listeners: Record<string, Closure> = {}

  listen (event: string, callback: Closure) {
    this.listeners[event] = callback
    return this
  }

  trigger (event: string, data: any) {
    this.listeners[event]?.(data)
  }
}

new class extends UnitTestCase {
  protected test () {
    const echo = new FakeEcho({
      broadcaster: 'null',
    })

    broadcastSubscriber.init('foo-id', echo)
    const privateChannel = echo.channels['user.foo-id']
    expect(privateChannel).toBeInstanceOf(FakeChannel)

    it('listens to .song.uploaded event', () => {
      const handleMock = this.mock(uploadService, 'handleUploadResult')
      privateChannel.trigger('.song.uploaded', 'some data')
      expect(handleMock).toHaveBeenCalledWith('some data')
    })
  }
}
