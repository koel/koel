import Axios, { AxiosInstance, Method } from 'axios'
import NProgress from 'nprogress'
import { eventBus } from '@/utils'
import { authService } from '@/services'

class Http {
  client: AxiosInstance

  private static setProgressBar () {
    NProgress.start()
  }

  private static hideProgressBar () {
    NProgress.done(true)
  }

  public request<T> (method: Method, url: string, data: Record<string, any> = {}, onUploadProgress?: any) {
    return this.client.request({
      url,
      data,
      method,
      onUploadProgress
    }) as Promise<{ data: T }>
  }

  public async get<T> (url: string) {
    return (await this.request<T>('get', url)).data
  }

  public async post<T> (url: string, data: Record<string, any>, onUploadProgress?: any) {
    return (await this.request<T>('post', url, data, onUploadProgress)).data
  }

  public async put<T> (url: string, data: Record<string, any>) {
    return (await this.request<T>('put', url, data)).data
  }

  public async delete<T> (url: string, data: Record<string, any> = {}) {
    return (await this.request<T>('delete', url, data)).data
  }

  constructor () {
    this.client = Axios.create({
      baseURL: `${window.BASE_URL}api`,
      headers: {
        'X-Api-Version': 'v6'
      }
    })

    // Intercept the request to make sure the token is injected into the header.
    this.client.interceptors.request.use(config => {
      Http.setProgressBar()
      config.headers.Authorization = `Bearer ${authService.getToken()}`
      return config
    })

    // Intercept the response and…
    this.client.interceptors.response.use(response => {
      Http.hideProgressBar()

      // …get the token from the header or response data if exists, and save it.
      const token = response.headers.authorization || response.data.token
      token && authService.setToken(token)

      return response
    }, error => {
      Http.hideProgressBar()

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

export const httpService = new Http()
