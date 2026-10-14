import { merge } from 'lodash-es'
import type { Reactive } from 'vue'
import { reactive } from 'vue'
import { arrayify } from '@/utils/helpers'

interface UseVaultOptions<T> {
  /** Invoked once for every item newly added to the vault, after it's wrapped reactive. */
  onItemAdded?: (item: Reactive<T>) => void
  /** Invoked for every item already in the vault, right before the incoming data is merged into it. */
  beforeItemMerged?: (existing: Reactive<T>, incoming: T) => void
}

export const useVault = <T extends object & { id: PropertyKey }>(options: UseVaultOptions<T> = {}) => {
  const vault = new Map<T['id'], Reactive<T>>()

  return {
    vault,

    byId: (id: T['id']): Reactive<T> | undefined => vault.get(id),

    syncWithVault: (items: MaybeArray<T>): Reactive<T>[] =>
      arrayify(items).map(item => {
        const existing = vault.get(item.id)

        if (existing) {
          options.beforeItemMerged?.(existing, item)
          merge(existing, item)
          return existing
        }

        const local = reactive(item)
        vault.set(item.id, local)
        options.onItemAdded?.(local)

        return local
      }),
  }
}
