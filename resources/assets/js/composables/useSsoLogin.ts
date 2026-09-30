import { openPopup } from '@/utils/helpers'

const isCompositeToken = (data: any): data is CompositeToken =>
  typeof data?.token === 'string' && typeof data?.['audio-token'] === 'string'

const isTwoFactorChallenge = (data: any): data is TwoFactorChallengeRequired =>
  data?.two_factor === true && typeof data?.login_token === 'string'

export const useSsoLogin = () => {
  let stopListening = () => {}

  const startSsoLogin = (
    redirectUrl: string,
    popupName: string,
    onLoginResponse: (loginResponse: LoginResponse) => void,
  ) => {
    stopListening()

    const popup = openPopup(redirectUrl, popupName, 768, 640, window)

    if (!popup) {
      throw new Error('Failed to open the SSO login window.')
    }

    const handleMessage = (message: MessageEvent) => {
      // The token is posted by a callback page that Koel itself serves, into the popup we
      // just opened. Anything from another origin or another window is a page trying to
      // hand us a token it made up.
      if (message.origin !== window.location.origin || message.source !== popup) {
        return
      }

      if (!isCompositeToken(message.data) && !isTwoFactorChallenge(message.data)) {
        return
      }

      stopListening()
      onLoginResponse(message.data)
    }

    window.addEventListener('message', handleMessage)
    stopListening = () => window.removeEventListener('message', handleMessage)
  }

  return { startSsoLogin }
}
