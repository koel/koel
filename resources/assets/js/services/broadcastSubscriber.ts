import type Echo from 'laravel-echo'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import type { UploadDuplication, UploadFailure, UploadResult } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'
import { makeReverbConnectionOptions } from '@/utils/reverbServer'
import { announceIfBuildChanged } from '@/utils/deployment'

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
    echo ||= await this.newEchoInstance()

    this.checkBuildOnReconnect(echo)
    this.subscribeToEvents(echo, userId)
  },

  /**
   * An app.updated broadcast sent while the tab was reconnecting is lost, so ask the server
   * for its build (in the X-Koel-Build header) whenever the connection comes back.
   */
  checkBuildOnReconnect: (echo: Echo<Broadcaster>) => {
    if (!('pusher' in echo.connector)) {
      return
    }

    let hasConnectedBefore = false

    echo.connector.pusher.connection.bind('state_change', ({ current }: { current: string }) => {
      if (current !== 'connected') {
        return
      }

      if (hasConnectedBefore) {
        http.silently.get('ping').catch(() => {})
      }

      hasConnectedBefore = true
    })
  },

  subscribeToEvents: (echo: Echo<Broadcaster>, userId: User['id']) => {
    echo.channel('koel').listen('.app.updated', ({ build }: { build: string }) => announceIfBuildChanged(build))

    return echo
      .private(`user.${userId}`)
      .listen('.song.uploaded', (event: UploadResult) => uploadService.handleUploadResult(event))
      .listen('.song.upload_failed', (event: UploadFailure) => uploadService.handleUploadFailure(event))
      .listen('.song.upload_duplicated', (event: UploadDuplication) => uploadService.handleUploadDuplication(event))
  },
}
