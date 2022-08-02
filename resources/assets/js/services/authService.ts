import { localStorageService } from '@/services'

const STORAGE_KEY = 'api-token'

export const authService = {
  getToken: () => localStorageService.get<string | null>(STORAGE_KEY),

  hasToken () {
    return Boolean(this.getToken())
  },

  setToken: (token: string) => localStorageService.set(STORAGE_KEY, token),
  destroy: () => localStorageService.remove(STORAGE_KEY)
}
