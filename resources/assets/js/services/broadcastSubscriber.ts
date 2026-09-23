import type Echo from 'laravel-echo'
import { authService } from '@/services/authService'
import type { UploadResult } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'

type Broadcaster = 'pusher' | 'null' // narrow down the supported broadcasters

export const broadcastSubscriber = {
  echo: null as Echo<Broadcaster> | null,

  async newEchoInstance(): Promise<Echo<Broadcaster>> {
    const { app_key: key, app_cluster: cluster } = window.KOEL.pusher

    const { default: EchoLib } = await import('laravel-echo')

    if (!key || !cluster) {
      return new EchoLib({
        broadcaster: 'null',
      })
    }

    const { default: Pusher } = await import('pusher-js')
    window.Pusher = window.Pusher || Pusher

    return new EchoLib({
      key,
      cluster,
      broadcaster: 'pusher',
      forceTLS: true,
      authEndpoint: `${window.KOEL.base_url}api/broadcasting/auth`,
      bearerToken: authService.getApiToken(),
    })
  },

  async init(userId: User['id'], echo?: Echo<'pusher' | 'null'>) {
    this.subscribeToEvents(echo || (await this.newEchoInstance()), userId)
  },

  subscribeToEvents: (echo: Echo<Broadcaster>, userId: User['id']) => {
    return echo
      .private(`user.${userId}`)
      .listen('.song.uploaded', (event: UploadResult) => uploadService.handleUploadResult(event))
  },
}
