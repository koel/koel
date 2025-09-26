import { vi } from 'vitest'

export const openContextMenu = vi.fn()
export const closeContextMenu = vi.fn()
export const trigger = vi.fn()

export const ContextMenu = {}
export const Separator = {}
export const MenuItem = {}

export const useContextMenu = () => ({
  openContextMenu,
  closeContextMenu,
  trigger,
  ContextMenu,
  Separator,
  MenuItem,
})
