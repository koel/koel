import { openPopup } from '@/utils/helpers'

export const useSsoLogin = () => {
  const startSsoLogin = (redirectUrl: string, popupName: string, onToken: (token: string) => void) => {
    window.onmessage = (message: MessageEvent) => {
      // The callback page that posts the token is served by Koel itself. A message from
      // anywhere else is another window trying to hand us a token it made up.
      if (message.origin !== window.location.origin) {
        return
      }

      onToken(message.data)
    }

    openPopup(redirectUrl, popupName, 768, 640, window)
  }

  return { startSsoLogin }
}
