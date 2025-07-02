import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { authService } from '@/services/authService'
import type { UploadResult } from '@/services/uploadService'
import { uploadService } from '@/services/uploadService'

export const broadcastListener = {
  echo: null as Echo<'pusher'> | null,

  init (userId: User['id']) {
    window.Pusher = window.Pusher || Pusher

    this.echo = new Echo({
      broadcaster: 'pusher',
      key: import.meta.env.VITE_PUSHER_APP_KEY,
      cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
      forceTLS: true,
      bearerToken: authService.getApiToken(),
    })

    this.echo.private(`user.${userId}`)
      .listen('.song.uploaded', (event: UploadResult) => {
        uploadService.handleUploadResult(event)
      })
  },
}
