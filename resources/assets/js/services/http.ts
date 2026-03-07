import ky, { HTTPError } from 'ky'
import NProgress from 'nprogress'
import { authService } from '@/services/authService'
import { eventBus } from '@/utils/eventBus'

export { HTTPError }

export const isHttpError = (error: unknown): error is HTTPError => error instanceof HTTPError

class Http {
  private client: ReturnType<typeof ky.create>
  private silent = false

  constructor() {
    this.client = ky.create({
      prefixUrl: `${window.BASE_URL}api`,
      headers: {
        'X-Api-Version': 'v7',
      },
      hooks: {
        beforeRequest: [
          request => {
            this.silent || this.showLoadingIndicator()
            request.headers.set('Authorization', `Bearer ${authService.getApiToken()}`)
          },
        ],
        afterResponse: [
          (_request, _options, response) => {
            this.silent || this.hideLoadingIndicator()
            this.silent = false

            const token = response.headers.get('authorization')
            token && authService.setApiToken(token)
          },
        ],
        beforeError: [
          async error => {
            this.silent || this.hideLoadingIndicator()
            this.silent = false

            const { response } = error

            if (response && (response.status === 400 || response.status === 401)) {
              const method = (error.request?.method || '').toLowerCase()

              let url = ''

              try {
                url = new URL(error.request?.url || '').pathname
              } catch {
                url = String(error.request?.url || '')
              }

              if (!(method === 'post' && url.endsWith('/me'))) {
                authService.setRedirect()
                eventBus.emit('LOG_OUT')
              }
            }

            // Attach parsed response data for error handlers
            try {
              ;(error as any).responseData = await response?.clone().json()
            } catch {
              ;(error as any).responseData = null
            }

            return error
          },
        ],
      },
      retry: 0,
      timeout: false,
      fetch: (...args: Parameters<typeof fetch>) => fetch(...args),
    })
  }

  public get silently() {
    this.silent = true
    return this
  }

  public async request<T>(method: string, url: string, data: Record<string, any> = {}) {
    const options: Record<string, any> = {}

    if (method !== 'get' && data) {
      if (data instanceof FormData) {
        options.body = data
      } else {
        options.json = data
      }
    }

    const response = await this.client(url, { method, ...options })
    const contentType = response.headers.get('content-type')
    const responseData = contentType?.includes('application/json') ? await response.json() : await response.text()

    return { data: responseData as T }
  }

  public async get<T>(url: string) {
    return (await this.request<T>('get', url)).data
  }

  public async post<T>(url: string, data: Record<string, any> = {}) {
    return (await this.request<T>('post', url, data)).data
  }

  public async put<T>(url: string, data: Record<string, any>) {
    return (await this.request<T>('put', url, data)).data
  }

  public async patch<T>(url: string, data: Record<string, any>) {
    return (await this.request<T>('patch', url, data)).data
  }

  public async delete<T>(url: string, data: Record<string, any> = {}) {
    return (await this.request<T>('delete', url, data)).data
  }

  private showLoadingIndicator() {
    NProgress.start()
  }

  private hideLoadingIndicator() {
    NProgress.done(true)
  }
}

export const http = new Http()

export { postWithProgress } from '@/services/httpUpload'
