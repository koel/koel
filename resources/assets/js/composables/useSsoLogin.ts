import { openPopup } from '@/utils/helpers'

export const useSsoLogin = () => {
  let stopListening = () => {}

  const startSsoLogin = (redirectUrl: string, popupName: string, onToken: (token: CompositeToken) => void) => {
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

      if (typeof message.data?.token !== 'string' || typeof message.data?.['audio-token'] !== 'string') {
        return
      }

      stopListening()
      onToken(message.data)
    }

    window.addEventListener('message', handleMessage)
    stopListening = () => window.removeEventListener('message', handleMessage)
  }

  return { startSsoLogin }
}
