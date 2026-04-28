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

  it('renders the popover root', () => {
    const { container } = h.render(Component, provide(shallowRef({ component: null, position: { top: 0, left: 0 } })))

    const root = container.querySelector<HTMLElement>('.context-menu[popover]')!
    expect(root).toBeTruthy()
    expect(root.getAttribute('popover')).toBe('manual')
    expect(root.getAttribute('role')).toBe('menu')
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
    const hideSpy = vi.spyOn(HTMLElement.prototype, 'hidePopover')
    const options = shallowRef<any>({
      component: { template: '<div>Menu</div>' },
      position: { top: 100, left: 200 },
    })

    h.render(Component, provide(options))

    await h.tick()

    options.value = {
      component: null,
      position: { top: 0, left: 0 },
    }

    await h.tick()

    expect(hideSpy).toHaveBeenCalled()
    hideSpy.mockRestore()
  })

  it('applies extra class', () => {
    const { container } = h.render(Component, {
      props: { extraClass: 'my-custom-class' },
      ...provide(shallowRef({ component: null, position: { top: 0, left: 0 } })),
    })

    expect(container.querySelector('.my-custom-class[popover]')).toBeTruthy()
  })
})
