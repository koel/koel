import { http } from '@/services/http'
import { cache } from '@/services/cache'

type ResourceType = 'album' | 'artist' | 'radio-station' | 'user'
type Action = 'edit' | 'delete'

export const acl = {
  checkResourcePermission: async (type: ResourceType, id: string | number, action: Action) => {
    return await cache.remember(['permission', type, id, action], async () => {
      const { allowed } = await http.silently.get<{ allowed: boolean }>(`acl/permissions/${type}/${id}/${action}`)

      return allowed
    })
  },

  fetchAssignableRoles: async () => {
    return await cache.remember(['assignable-roles'], async () => {
      const { roles } = await http.get<{
        roles: Array<{ id: Role, label: string, description: string }>
      }>('acl/assignable-roles')

      return roles
    })
  },
}
