import type { CompositeToken } from '@/services/authService'
import { authService } from '@/services/authService'
import { http } from '@/services/http'
import { userStore } from '@/stores/userStore'

export const invitationService = {
  getUserProspect: async (token: string) => await http.get<User>(`invitations?token=${token}`),

  async accept (token: string, name: string, password: string) {
    const compositeToken = await http.post<CompositeToken>('invitations/accept', { token, name, password })

    authService.setAudioToken(compositeToken['audio-token'])
    authService.setApiToken(compositeToken.token)
  },

  invite: async (emails: string[], isAdmin: boolean) => {
    const users = await http.post<User[]>('invitations', { emails, is_admin: isAdmin })
    userStore.add(users)
  },

  revoke: async (user: User) => {
    await http.delete(`invitations`, { email: user.email })
    userStore.remove(user)
  },
}
