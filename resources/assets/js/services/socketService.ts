import { userStore } from '@/stores/userStore'
import { authService } from '@/services/authService'
import { makeReverbConnectionOptions } from '@/utils/reverbServer'

export const socketService = {
  pusher: null as any,
  channel: null as any,

  async init() {
    const { pusher, reverb } = window.KOEL
    const key = reverb?.app_key || pusher.app_key

    if (!key) {
      return false
    }

    const { default: PusherLib } = await import('pusher-js')

    const connectionOptions = reverb
      ? { cluster: '', ...makeReverbConnectionOptions(reverb) }
      : { cluster: pusher.app_cluster, forceTLS: true }

    this.pusher = new PusherLib(key, {
      channelAuthorization: {
        endpoint: `${window.KOEL.base_url}api/broadcasting/auth`,
        transport: 'ajax',
        headers: {
          Authorization: `Bearer ${authService.getApiToken()}`,
        },
      },
      ...connectionOptions,
    })

    this.channel = this.pusher.subscribe(`private-koel.${userStore.current.id}`)

    return true
  },

  broadcast(eventName: string, data: any = {}) {
    this.channel?.trigger(`client-${eventName}`, data)
    return this
  },

  listen(eventName: string, cb: Closure) {
    this.channel?.bind(`client-${eventName}`, data => cb(data))
    return this
  },
}
