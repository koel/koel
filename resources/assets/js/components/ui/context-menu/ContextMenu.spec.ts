import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { shallowRef } from 'vue'
import { ContextMenuKey } from '@/config/symbols'
import Component from './ContextMenu.vue'

describe('contextMenu', () => {
  const h = createHarness()

  const provide = (options: ReturnType<typeof shallowRef>) => ({
    global: {
      provide: {
        [ContextMenuKey as symbol]: options,
      },
    },
  })

  it('opens when options.component is set', async () => {
    const showSpy = vi.spyOn(HTMLElement.prototype, 'showPopover')
    const options = shallowRef<any>({
      component: null,
      position: { top: 0, left: 0 },
    })

    h.render(Component, provide(options))

    options.value = {
      component: { template: '<div>Menu Content</div>' },
      position: { top: 100, left: 200 },
    }

    await h.tick(2)

    expect(showSpy).toHaveBeenCalled()
    showSpy.mockRestore()
  })

  it('closes when options.component is cleared', async () => {
    const showSpy = vi.spyOn(HTMLElement.prototype, 'showPopover')
    const hideSpy = vi.spyOn(HTMLElement.prototype, 'hidePopover')
    const options = shallowRef<any>({
      component: null,
      position: { top: 0, left: 0 },
    })

    h.render(Component, provide(options))

    // Open the menu first so that close can transition from open → closed.
    options.value = {
      component: { template: '<div>Menu</div>' },
      position: { top: 100, left: 200 },
    }

    await h.tick(2)

    options.value = {
      component: null,
      position: { top: 0, left: 0 },
    }

    await h.tick()

    expect(hideSpy).toHaveBeenCalled()
    showSpy.mockRestore()
    hideSpy.mockRestore()
  })
})
