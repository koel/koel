import Axios, { AxiosInstance, Method } from 'axios'
import NProgress from 'nprogress'

import { eventBus } from '@/utils'
import { authService } from '@/services'

export const httpService = {
  client: null as AxiosInstance | null,

  setProgressBar: () => NProgress.start(),
  hideProgressBar: () => NProgress.done(true),

  request<T> (method: Method, url: string, data: Record<string, any> = {}, onUploadProgress?: any) {
    return this.client!.request({
      url,
      data,
      method,
      onUploadProgress
    }) as Promise<{ data: T }>
  },

  async get<T> (url: string) {
    return (await this.request<T>('get', url)).data
  },

  async post<T> (url: string, data: Record<string, any>, onUploadProgress?: any) {
    return (await this.request<T>('post', url, data, onUploadProgress)).data
  },

  async put<T> (url: string, data: Record<string, any>) {
    return (await this.request<T>('put', url, data)).data
  },

  async delete<T> (url: string, data: Record<string, any> = {}) {
    return (await this.request<T>('delete', url, data)).data
  },

  init () {
    this.client = Axios.create({
      baseURL: `${window.BASE_URL}api`
    })

    // Intercept the request to make sure the token is injected into the header.
    this.client.interceptors.request.use(config => {
      this.setProgressBar()
      config.headers.Authorization = `Bearer ${authService.getToken()}`
      return config
    })

    // Intercept the response and…
    this.client.interceptors.response.use(response => {
      this.hideProgressBar()

      // …get the token from the header or response data if exists, and save it.
      const token = response.headers.Authorization || response.data.token
      token && authService.setToken(token)

      return response
    }, error => {
      this.hideProgressBar()

      // Also, if we receive a Bad Request / Unauthorized error
      if (error.response.status === 400 || error.response.status === 401) {
        // and we're not trying to log in
        if (!(error.config.method === 'post' && /\/api\/me\/?$/.test(error.config.url))) {
          // the token must have expired. Log out.
          eventBus.emit('LOG_OUT')
        }
      }

      return Promise.reject(error)
    })
  }
}
