import type Echo from 'laravel-echo'
import { authService } from '@/services/authService'
import type { UploadDuplication, UploadFailure, UploadResult } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import { makeReverbConnectionOptions } from '@/utils/reverbServer'

type Broadcaster = 'reverb' | 'pusher' | 'null' // narrow down the supported broadcasters

export const broadcastSubscriber = {
  echo: null as Echo<Broadcaster> | null,

  async newEchoInstance(): Promise<Echo<Broadcaster>> {
    const { pusher, reverb } = window.KOEL
    const { app_key: key, app_cluster: cluster } = pusher

    const { default: EchoLib } = await import('laravel-echo')

    if (!reverb && (!key || !cluster)) {
      return new EchoLib({
        broadcaster: 'null',
      })
    }

    const { default: Pusher } = await import('pusher-js')
    window.Pusher = window.Pusher || Pusher

    if (reverb) {
      return new EchoLib({
        key: reverb.app_key,
        broadcaster: 'reverb',
        ...makeReverbConnectionOptions(reverb),
        authEndpoint: `${window.KOEL.base_url}api/broadcasting/auth`,
        bearerToken: authService.getApiToken(),
      })
    }

    return new EchoLib({
      key,
      cluster,
      broadcaster: 'pusher',
      forceTLS: true,
      authEndpoint: `${window.KOEL.base_url}api/broadcasting/auth`,
      bearerToken: authService.getApiToken(),
    })
  },

  async init(userId: User['id'], echo?: Echo<Broadcaster>) {
    this.subscribeToEvents(echo || (await this.newEchoInstance()), userId)
  },

  subscribeToEvents: (echo: Echo<Broadcaster>, userId: User['id']) => {
    return echo
      .private(`user.${userId}`)
      .listen('.song.uploaded', (event: UploadResult) => uploadService.handleUploadResult(event))
      .listen('.song.upload_failed', (event: UploadFailure) => uploadService.handleUploadFailure(event))
      .listen('.song.upload_duplicated', (event: UploadDuplication) => uploadService.handleUploadDuplication(event))
  },
}
