import { merge } from 'lodash-es'
import { http } from '@/services/http'
import { userStore } from '@/stores/userStore'
import { useLocalStorage } from '@/composables/useLocalStorage'
import { use } from '@/utils/helpers'

export interface UpdateCurrentProfileData {
  name: string
  email: string
  avatar?: string
}

const API_TOKEN_STORAGE_KEY = 'api-token'
const AUDIO_TOKEN_STORAGE_KEY = 'audio-token'
const REDIRECT_KEY = 'redirect'

const { get: lsGet, set: lsSet, remove: lsRemove } = useLocalStorage(false) // authentication local storage data aren't namespaced

const isTwoFactorChallengeRequired = (response: LoginResponse): response is TwoFactorChallengeRequired => {
  return 'two_factor' in response && response.two_factor
}

export const authService = {
  async login(email: string, password: string): Promise<TwoFactorChallengeRequired | null> {
    const response = await http.post<LoginResponse>('me', { email, password })

    if (isTwoFactorChallengeRequired(response)) {
      return response
    }

    this.setTokensUsingCompositeToken(response)
    this.maybeRedirect()
    return null
  },

  async submitTwoFactorChallenge(loginToken: string, code: string) {
    this.setTokensUsingCompositeToken(
      await http.post<CompositeToken>('me/two-factor-challenge', { login_token: loginToken, code }),
    )
    this.maybeRedirect()
  },

  enrollTwoFactor: async () => await http.post<{ provisioning_uri: string }>('me/two-factor'),

  confirmTwoFactor: async (code: string) =>
    await http.post<{ recovery_codes: string[] }>('me/two-factor/confirm', { code }),

  disableTwoFactor: async (code: string) => await http.delete('me/two-factor', { code }),

  regenerateRecoveryCodes: async (code: string) =>
    await http.post<{ recovery_codes: string[] }>('me/two-factor/recovery-codes', { code }),

  async logout() {
    await http.delete('me')
    this.destroy()
  },

  getProfile: async () => await http.get<User>('me'),

  updateProfile: async (data: UpdateCurrentProfileData) => {
    merge(userStore.current, await http.put<User>('me', data))
  },

  changePassword: async (currentPassword: string, newPassword: string) => {
    await http.put('me/password', { current_password: currentPassword, new_password: newPassword })
  },

  getApiToken: () => lsGet<string>(API_TOKEN_STORAGE_KEY),

  hasApiToken() {
    return Boolean(this.getApiToken())
  },

  setApiToken: (token: string) => lsSet(API_TOKEN_STORAGE_KEY, token),

  setTokensUsingCompositeToken(compositeToken: CompositeToken) {
    this.setApiToken(compositeToken.token)
    this.setAudioToken(compositeToken['audio-token'])
  },

  destroy: () => {
    lsRemove(API_TOKEN_STORAGE_KEY)
    lsRemove(AUDIO_TOKEN_STORAGE_KEY)
  },

  setAudioToken: (token: string) => lsSet(AUDIO_TOKEN_STORAGE_KEY, token),

  getAudioToken: () => {
    // for backward compatibility, we first try to get the audio token, and fall back to the (full-privileged) API token
    return lsGet(AUDIO_TOKEN_STORAGE_KEY) || lsGet(API_TOKEN_STORAGE_KEY)
  },

  requestResetPasswordLink: async (email: string) => await http.post('forgot-password', { email }),

  resetPassword: async (email: string, password: string, token: string) => {
    return await http.post('reset-password', { email, password, token })
  },

  getOneTimeToken: async () => (await http.get<{ token: string }>('one-time-token')).token,

  setRedirect: (url?: string) => lsSet(REDIRECT_KEY, url || location.toString()),

  hasRedirect: () => Boolean(lsGet(REDIRECT_KEY)),

  maybeRedirect: () =>
    use(lsGet<string | null>(REDIRECT_KEY), url => {
      lsRemove(REDIRECT_KEY)
      location.assign(url)
    }),
}
