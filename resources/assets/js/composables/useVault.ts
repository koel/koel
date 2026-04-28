import { merge } from 'lodash'
import { reactive } from 'vue'
import { arrayify } from '@/utils/helpers'

export const useVault = <T extends { id: PropertyKey }>() => {
  const vault = new Map<T['id'], T>()

  return {
    vault,

    byId: (id: T['id']) => vault.get(id),

    syncWithVault: (items: MaybeArray<T>): T[] =>
      arrayify(items).map(item => {
        const existing = vault.get(item.id)
        const local = reactive(existing ? merge(existing, item) : item) as T
        vault.set(item.id, local)

        return local
      }),
  }
}
