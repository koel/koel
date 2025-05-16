import { http } from '@/services/http'
import { cache } from '@/services/cache'

type ResourceType = 'album' | 'artist'
type Action = 'edit'

export const resourcePermissionService = {
  check: async (type: ResourceType, id: string | number, action: Action) => {
    return await cache.remember<boolean>(['permission', type, id, action], async () => {
      const response = await http.silently.get<{ allowed: boolean }>(`permissions/${type}/${id}/${action}`)

      return response.allowed
    })
  },
}
