import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import { defineComponent, shallowRef } from 'vue'
import { ContextMenuKey } from '@/config/symbols'
import Component from './ContextMenu.vue'
import ContextMenuItem from './ContextMenuItem.vue'

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

  it('marks a submenu item expanded while its submenu is open', async () => {
    h.mock(HTMLElement.prototype, 'showPopover')

    const MenuWithSubmenu = defineComponent({
      components: { ContextMenuItem },
      template: `
        <ul>
          <ContextMenuItem>
            Add to
            <template #subMenuItems>
              <ContextMenuItem>Playlist</ContextMenuItem>
            </template>
          </ContextMenuItem>
        </ul>
      `,
    })

    const options = shallowRef<any>({ component: null, position: { top: 0, left: 0 } })
    h.render(Component, provide(options))

    options.value = { component: MenuWithSubmenu, position: { top: 100, left: 200 } }
    await h.tick(2)

    const parentItem = screen.getAllByRole('menuitem', { hidden: true })[0]
    parentItem.focus()

    await h.user.keyboard('{ArrowRight}')
    await h.tick(2)
    expect(parentItem.getAttribute('aria-expanded')).toBe('true')

    screen.getAllByRole('menuitem', { hidden: true })[1].focus()
    await h.user.keyboard('{ArrowLeft}')
    expect(parentItem.getAttribute('aria-expanded')).toBe('false')
  })
})
