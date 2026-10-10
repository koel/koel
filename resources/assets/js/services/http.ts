import ky, { HTTPError } from 'ky'
import NProgress from 'nprogress'
import type { ServerValidationError } from '@/utils/formatters'
import { authService } from '@/services/authService'
import { eventBus } from '@/utils/eventBus'

export { HTTPError }

export type HttpErrorBody = Partial<ServerValidationError>

export const isHttpError = (error: unknown): error is HTTPError<HttpErrorBody> => error instanceof HTTPError

export const getHttpErrorBody = (error: HTTPError<HttpErrorBody>) =>
  typeof error.data === 'object' ? error.data : undefined

type RetryPolicy = 'none' | 'repeatable' | 'unprocessed'

const RETRY_BACKOFF_LIMIT_MS = 10_000

const retryOptions = {
  repeatable: { limit: 3, statusCodes: [408, 429, 500, 502, 503, 504], backoffLimit: RETRY_BACKOFF_LIMIT_MS },
  unprocessed: { limit: 3, statusCodes: [429, 502, 503], backoffLimit: RETRY_BACKOFF_LIMIT_MS },
}

class Http {
  private client: ReturnType<typeof ky.create>
  private silent = false
  private retryPolicy: RetryPolicy = 'none'

  constructor() {
    this.client = ky.create({
      prefix: `${window.KOEL.base_url}api`,
      headers: {
        Accept: 'application/json',
        'X-Api-Version': 'v7',
      },
      hooks: {
        beforeRequest: [
          ({ request }) => {
            this.silent || this.showLoadingIndicator()
            request.headers.set('Authorization', `Bearer ${authService.getApiToken()}`)
          },
        ],
        afterResponse: [
          ({ response }) => {
            this.silent || this.hideLoadingIndicator()
            this.silent = false

            const token = response.headers.get('authorization')
            token && authService.setApiToken(token)

            const build = response.headers.get('x-koel-build')

            if (build && window.KOEL.build && build !== window.KOEL.build) {
              eventBus.emit('NEW_VERSION_DEPLOYED')
            }
          },
        ],
        beforeError: [
          ({ error }) => {
            this.silent || this.hideLoadingIndicator()
            this.silent = false

            if (isHttpError(error) && (error.response.status === 400 || error.response.status === 401)) {
              const method = (error.request?.method || '').toLowerCase()

              let url = ''

              try {
                url = new URL(error.request?.url || '').pathname
              } catch {
                url = error.request?.url || ''
              }

              const isAuthEntryPoint =
                method === 'post' &&
                (url.endsWith('/me') || url.endsWith('/me/two-factor-challenge') || url.endsWith('/me/otp'))

              if (!isAuthEntryPoint) {
                authService.setRedirect()
                eventBus.emit('LOG_OUT')
              }
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

  /**
   * Retry the next request on network errors and temporary server errors.
   * Only for requests that are safe to send more than once, like saving a state.
   */
  public get withRetries() {
    this.retryPolicy = 'repeatable'
    return this
  }

  /**
   * Retry the next request only when the server clearly did not handle it.
   * For requests that add something, like a play or a scrobble, where a repeat could count twice.
   */
  public get withRetriesWhenUnprocessed() {
    this.retryPolicy = 'unprocessed'
    return this
  }

  public async request<T>(method: string, url: string, data: Record<string, any> = {}) {
    const options: Record<string, any> = {}
    const retryPolicy = this.retryPolicy
    this.retryPolicy = 'none'

    if (retryPolicy !== 'none') {
      options.retry = { ...retryOptions[retryPolicy], methods: [method] }
    }

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
