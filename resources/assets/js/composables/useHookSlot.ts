import { computed } from 'vue'
import type { Component } from 'vue'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'

export const useHookSlot = (name: HookSlotName, context: () => Record<string, unknown> = () => ({})) =>
  computed(() => applyFilters<Component[]>(Filter.SLOT, [], name, context()))
