import { ls } from '.'

export const auth = {
  storageKey: 'api-token',

  getToken (): string | null {
    return ls.get<string | null>(this.storageKey)
  },

  hasToken (): boolean {
    return Boolean(this.getToken())
  },

  setToken (token: string): void {
    ls.set(this.storageKey, token)
  },

  destroy (): void {
    ls.remove(this.storageKey)
  }
}
