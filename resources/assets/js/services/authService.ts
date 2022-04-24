import { localStorageService } from '.'

export const authService = {
  storageKey: 'api-token',

  getToken () {
    return localStorageService.get<string | null>(this.storageKey)
  },

  hasToken () {
    return Boolean(this.getToken())
  },

  setToken (token: string) {
    localStorageService.set(this.storageKey, token)
  },

  destroy () {
    localStorageService.remove(this.storageKey)
  }
}
