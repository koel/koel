import { computed, toValue } from 'vue'
import type { Component, MaybeRefOrGetter } from 'vue'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'

export const useHookSlot = (
  name: MaybeRefOrGetter<HookSlotName>,
  context: MaybeRefOrGetter<Record<string, unknown>> = {},
) => computed(() => applyFilters<Component[]>(Filter.SLOT, [], toValue(name), toValue(context)))
