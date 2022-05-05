import Pusher from 'pusher-js'

import { userStore } from '@/stores'
import { authService } from '.'

export const socketService = {
  pusher: null as Pusher.Pusher | null,
  channel: null as Pusher.Channel | null,

  async init (): Promise<void> {
    return new Promise(resolve => {
      if (!window.PUSHER_APP_KEY) {
        return resolve()
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
      this.channel.bind('pusher:subscription_succeeded', () => resolve())
      this.channel.bind('pusher:subscription_error', () => resolve())
    })
  },

  broadcast (eventName: string, data: any = {}) {
    this.channel && this.channel.trigger(`client-${eventName}.${userStore.current.id}`, data)
    return this
  },

  listen (eventName: string, cb: Closure) {
    this.channel && this.channel.bind(`client-${eventName}.${userStore.current.id}`, data => cb(data))
    return this
  }
}
