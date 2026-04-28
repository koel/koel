import { merge } from 'lodash'
import { reactive } from 'vue'
import { arrayify } from '@/utils/helpers'

interface UseVaultOptions<T> {
  /** Invoked once for every item newly added to the vault, after it's wrapped reactive. */
  onItemAdded?: (item: T) => void
}

export const useVault = <T extends { id: PropertyKey }>(options: UseVaultOptions<T> = {}) => {
  const vault = new Map<T['id'], T>()

  return {
    vault,

    byId: (id: T['id']) => vault.get(id),

    syncWithVault: (items: MaybeArray<T>): T[] =>
      arrayify(items).map(item => {
        const existing = vault.get(item.id)

        if (existing) {
          merge(existing, item)
          return existing
        }

        const local = reactive(item) as T
        options.onItemAdded?.(local)
        vault.set(item.id, local)

        return local
      }),
  }
}
