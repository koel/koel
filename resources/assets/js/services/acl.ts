import { http } from '@/services/http'
import { cache } from '@/services/cache'

export const acl = {
  fetchAssignableRoles: async () => {
    return await cache.remember(['assignable-roles'], async () => {
      const { roles } = await http.get<{
        roles: Array<{ id: Role; label: string; description: string }>
      }>('acl/assignable-roles')

      return roles
    })
  },
}
