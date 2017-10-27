import Pusher from 'pusher-js'

import { userStore } from '@/stores'
import { ls } from '.'

export const socket = {
  pusher: null,
  channel: null,

  async init () {
    return new Promise((resolve, reject) => {
      if (!window.PUSHER_APP_KEY) {
        return resolve()
      }

      this.pusher = new Pusher(window.PUSHER_APP_KEY, {
        authEndpoint: `${window.BASE_URL}api/broadcasting/auth`,
        auth: {
          headers: {
            Authorization: `Bearer ${ls.get('jwt-token')}`
          }
        },
        cluster: window.PUSHER_APP_CLUSTER,
        encrypted: true
      })

      this.channel = this.pusher.subscribe('private-koel')
      this.channel.bind('pusher:subscription_succeeded', () => {
        return resolve()
      })
      this.channel.bind('pusher:subscription_error', () => {
        return resolve()
      })
    })
  },

  /**
   * Broadcast an event with Pusher.
   * @param  {string} eventName The event's name
   * @param  {Object} data      The event's data
   */
  broadcast (eventName, data = {}) {
    this.channel && this.channel.trigger(`client-${eventName}.${userStore.current.id}`, data)

    return this
  },

  /**
   * Listen to an event.
   * @param  {string}   eventName The event's name
   * @param  {Function} cb
   */
  listen (eventName, cb) {
    this.channel && this.channel.bind(`client-${eventName}.${userStore.current.id}`, data => cb(data))

    return this
  }
}
