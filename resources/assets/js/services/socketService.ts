import Pusher from 'pusher-js'

import { userStore } from '@/stores'
import { authService } from '@/services'

export const socketService = {
  pusher: null as Pusher.Pusher | null,
  channel: null as Pusher.Channel | null,

  async init () {
    if (!window.PUSHER_APP_KEY) {
      return false
    }

    this.pusher = new Pusher(window.PUSHER_APP_KEY, {
      authEndpoint: `${window.BASE_URL}api/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${authService.getToken()}`
        }
      },
      cluster: window.PUSHER_APP_CLUSTER,
      encrypted: true
    })

    this.channel = this.pusher.subscribe('private-koel')

    return true
  },

  broadcast (eventName: string, data: any = {}) {
    this.channel?.trigger(`client-${eventName}.${userStore.current.id}`, data)
    return this
  },

  listen (eventName: string, cb: Closure) {
    this.channel?.bind(`client-${eventName}.${userStore.current.id}`, data => cb(data))
    return this
  }
}
