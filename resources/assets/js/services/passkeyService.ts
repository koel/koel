import { http } from '@/services/http'
import { authService } from '@/services/authService'

interface PasskeyLoginOptions {
  options: PublicKeyCredentialRequestOptionsJSON
  login_token: string
}

export const isPasskeySupported = () => typeof window.PublicKeyCredential?.parseRequestOptionsFromJSON === 'function'

const ensurePublicKeyCredential = (credential: Credential | null) => {
  if (!(credential instanceof PublicKeyCredential)) {
    throw new TypeError('The browser returned no passkey.')
  }

  return credential
}

export const isPasskeyPromptDismissed = (error: unknown) =>
  error instanceof DOMException && error.name === 'NotAllowedError'

export const passkeyService = {
  async logIn() {
    const { options, login_token } = await http.get<PasskeyLoginOptions>('me/passkey-login-options')

    const credential = await navigator.credentials.get({
      publicKey: PublicKeyCredential.parseRequestOptionsFromJSON(options),
    })

    authService.setTokensUsingCompositeToken(
      await http.post<CompositeToken>('me/passkey-login', {
        login_token,
        credential: ensurePublicKeyCredential(credential).toJSON(),
      }),
    )

    authService.maybeRedirect()
  },

  fetchAll: async () => await http.get<Passkey[]>('me/passkeys'),

  async add(name: string) {
    const options = await http.get<PublicKeyCredentialCreationOptionsJSON>('me/passkeys/registration-options')

    const credential = await navigator.credentials.create({
      publicKey: PublicKeyCredential.parseCreationOptionsFromJSON(options),
    })

    return await http.post<Passkey>('me/passkeys', {
      name,
      credential: ensurePublicKeyCredential(credential).toJSON(),
    })
  },

  remove: async (passkey: Passkey) => await http.delete(`me/passkeys/${passkey.id}`),
}
