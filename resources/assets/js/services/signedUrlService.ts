import { http } from '@/services/http'

const REUSE_MARGIN_SECONDS = 10 * 60

const urlsByEndpoint = new Map<string, string>()

const getSecondsUntilExpiry = (url: string) => {
  const expiresAt = Number(new URL(url, location.href).searchParams.get('expires'))

  return expiresAt ? expiresAt - Date.now() / 1000 : Infinity
}

const isFresh = (url: string) => getSecondsUntilExpiry(url) > REUSE_MARGIN_SECONDS

export const signedUrlService = {
  async fetch(endpoint: string) {
    const cached = this.getCached(endpoint)

    if (cached) {
      return cached
    }

    const { url } = await http.silently.get<{ url: string }>(endpoint)
    urlsByEndpoint.set(endpoint, url)

    return url
  },

  getCached(endpoint: string) {
    const cached = urlsByEndpoint.get(endpoint)

    return cached && isFresh(cached) ? cached : undefined
  },

  hasExpired: (url: string) => getSecondsUntilExpiry(url) <= 0,

  forget(endpoint: string) {
    urlsByEndpoint.delete(endpoint)
  },
}
