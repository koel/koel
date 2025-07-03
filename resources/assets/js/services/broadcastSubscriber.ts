import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { authService } from '@/services/authService'
import type { UploadResult } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'

type Broadcaster = 'pusher' | 'null' // narrow down the supported broadcasters

export const broadcastSubscriber = {
  echo: null as Echo<Broadcaster> | null,

  newEchoInstance (): Echo<Broadcaster> {
    const key = import.meta.env.VITE_PUSHER_APP_KEY
    const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER

    if (!key || !cluster) {
      return new Echo({
        broadcaster: 'null',
      })
    }

    window.Pusher = window.Pusher || Pusher

    return new Echo({
      key,
      cluster,
      broadcaster: 'pusher',
      forceTLS: true,
      bearerToken: authService.getApiToken(),
    })
  },

  init (userId: User['id'], echo?: Echo<'pusher' | 'null'>) {
    this.subscribeToEvents(echo || this.newEchoInstance(), userId)
  },

  subscribeToEvents: (echo: Echo<Broadcaster>, userId: User['id']) => {
    return echo
      .private(`user.${userId}`)
      .listen('.song.uploaded', (event: UploadResult) => uploadService.handleUploadResult(event))
  },
}
