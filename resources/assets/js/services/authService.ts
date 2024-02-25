import { merge } from 'lodash'
import { http, localStorageService } from '@/services'
import { userStore } from '@/stores'

export interface UpdateCurrentProfileData {
  current_password: string | null
  name: string
  email: string
  avatar?: string
  new_password?: string
}

export interface CompositeToken {
  'audio-token': string
  'token': string
}

const API_TOKEN_STORAGE_KEY = 'api-token'
const AUDIO_TOKEN_STORAGE_KEY = 'audio-token'

export const authService = {
  async login (email: string, password: string) {
    const token = await http.post<CompositeToken>('me', { email, password })

    this.setAudioToken(token['audio-token'])
    this.setApiToken(token.token)
  },

  async logout () {
    await http.delete('me')
    this.destroy()
  },

  getProfile: async () => await http.get<User>('me'),

  updateProfile: async (data: UpdateCurrentProfileData) => {
    merge(userStore.current, (await http.put<User>('me', data)))
  },

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
