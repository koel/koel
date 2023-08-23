import { http } from '@/services'
import { userStore } from '@/stores'

export const invitationService = {
  getUserProspect: async (token: string) => await http.get<User>(`invitations/?token=${token}`),

  async accept (token: string, name: string, password: string) {
    await http.post<User>('invitations/accept', { token, name, password })
  },

  invite: async (emails: string[], isAdmin: boolean) => {
    const users = await http.post<User[]>('invitations', { emails, is_admin: isAdmin })
    users.forEach(user => userStore.add(user))
  },

  revoke: async (user: User) => {
    await http.delete(`invitations`, { email: user.email })
    userStore.remove(user)
  }
}
