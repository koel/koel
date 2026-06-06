import { userStore } from '@/stores/userStore'
import { authService } from '@/services/authService'

export const socketService = {
  pusher: null as any,
  channel: null as any,

  async init() {
    if (!window.KOEL.pusher.app_key) {
      return false
    }

    const { default: PusherLib } = await import('pusher-js')

    this.pusher = new PusherLib(window.KOEL.pusher.app_key, {
      authEndpoint: `${window.KOEL.base_url}api/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${authService.getApiToken()}`,
        },
      },
      cluster: window.KOEL.pusher.app_cluster,
      encrypted: true,
    })

    this.channel = this.pusher.subscribe('private-koel')

    return true
  },

  broadcast(eventName: string, data: any = {}) {
    this.channel?.trigger(`client-${eventName}.${userStore.current.id}`, data)
    return this
  },

  listen(eventName: string, cb: Closure) {
    this.channel?.bind(`client-${eventName}.${userStore.current.id}`, data => cb(data))
    return this
  },
}
