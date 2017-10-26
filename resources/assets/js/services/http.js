import axios from 'axios'
import NProgress from 'nprogress'

import { event } from '@/utils'
import { ls } from '@/services'

/**
 * Responsible for all HTTP requests.
 */
export const http = {
  request (method, url, data, successCb = null, errorCb = null) {
    method = method.toLowerCase()
    axios.request({ url, data, method }).then(successCb).catch(errorCb)
  },

  get (url, successCb = null, errorCb = null) {
    return this.request('get', url, {}, successCb, errorCb)
  },

  post (url, data, successCb = null, errorCb = null) {
    return this.request('post', url, data, successCb, errorCb)
  },

  put (url, data, successCb = null, errorCb = null) {
    return this.request('put', url, data, successCb, errorCb)
  },

  delete (url, data = {}, successCb = null, errorCb = null) {
    return this.request('delete', url, data, successCb, errorCb)
  },

  /**
   * Init the service.
   */
  init () {
    axios.defaults.baseURL = `${window.BASE_URL}api`

    // Intercept the request to make sure the token is injected into the header.
    axios.interceptors.request.use(config => {
      config.headers.Authorization = `Bearer ${ls.get('jwt-token')}`
      return config
    })

    // Intercept the response and…
    axios.interceptors.response.use(response => {
      NProgress.done()

      // …get the token from the header or response data if exists, and save it.
      const token = response.headers['Authorization'] || response.data['token']
      token && ls.set('jwt-token', token)

      return response
    }, error => {
      NProgress.done()
      // Also, if we receive a Bad Request / Unauthorized error
      if (error.response.status === 400 || error.response.status === 401) {
        // and we're not trying to login
        if (!(error.config.method === 'post' && /\/api\/me\/?$/.test(error.config.url))) {
          // the token must have expired. Log out.
          event.emit('logout')
        }
      }

      return Promise.reject(error)
    })
  }
}
