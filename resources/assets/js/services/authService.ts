import { localStorageService } from '@/services'

const API_TOKEN_STORAGE_KEY = 'api-token'
const AUDIO_TOKEN_STORAGE_KEY = 'audio-token'

export const authService = {
  getApiToken: () => localStorageService.get(API_TOKEN_STORAGE_KEY),

  hasApiToken () {
    return Boolean(this.getApiToken())
  },

  setApiToken: (token: string) => localStorageService.set(API_TOKEN_STORAGE_KEY, token),

  destroy: () => {
    localStorageService.remove(API_TOKEN_STORAGE_KEY)
    localStorageService.remove(AUDIO_TOKEN_STORAGE_KEY)
  },

  setAudioToken: (token: string) => localStorageService.set(AUDIO_TOKEN_STORAGE_KEY, token),

  getAudioToken: () => {
    // for backward compatibility, we first try to get the audio token, and fall back to the (full-privileged) API token
    return localStorageService.get(AUDIO_TOKEN_STORAGE_KEY) || localStorageService.get(API_TOKEN_STORAGE_KEY)
  }
}
