import { describe, expect, it, vi } from 'vitest'
import { ref } from 'vue'
import { markRaw } from 'vue'

const contextMenuOptions = ref<any>({
  component: null,
  position: { top: 0, left: 0 },
})

vi.mock('@/utils/helpers', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/helpers')>()),
  requireInjection: () => contextMenuOptions,
}))

import { useContextMenu } from './useContextMenu'

describe('useContextMenu', () => {
  it('opens a context menu at a position', () => {
    const { openContextMenu } = useContextMenu()
    const FakeComponent = markRaw({ template: '<div />' })

    openContextMenu(FakeComponent, { top: 100, left: 200 })

    expect(contextMenuOptions.value.component).toBe(FakeComponent)
    expect(contextMenuOptions.value.position).toEqual({ top: 100, left: 200 })
  })

  it('opens from a mouse event', () => {
    const { openContextMenu } = useContextMenu()
    const FakeComponent = markRaw({ template: '<div />' })
    const event = new MouseEvent('contextmenu', { clientX: 50, clientY: 75 })

    openContextMenu(FakeComponent, event)

    expect(contextMenuOptions.value.position).toEqual({ top: 75, left: 50 })
  })

  it('closes context menu', () => {
    const { openContextMenu, closeContextMenu } = useContextMenu()
    const FakeComponent = markRaw({ template: '<div />' })

    openContextMenu(FakeComponent, { top: 100, left: 200 })
    closeContextMenu()

    expect(contextMenuOptions.value.component).toBeNull()
  })

  it('triggers a function and closes', () => {
    const { openContextMenu, trigger } = useContextMenu()
    const FakeComponent = markRaw({ template: '<div />' })
    const fn = vi.fn()

    openContextMenu(FakeComponent, { top: 100, left: 200 })
    trigger(fn)

    expect(fn).toHaveBeenCalled()
    expect(contextMenuOptions.value.component).toBeNull()
  })
})
