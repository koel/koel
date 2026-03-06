import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { shallowRef } from 'vue'
import { ContextMenuKey } from '@/config/symbols'
import Component from './ContextMenu.vue'

describe('contextMenu', () => {
  const h = createHarness({
    beforeEach: () => {
      HTMLDialogElement.prototype.showModal = vi.fn()
      HTMLDialogElement.prototype.close = vi.fn()
    },
  })

  it('renders a dialog element', () => {
    h.render(Component, {
      global: {
        provide: {
          [ContextMenuKey as symbol]: shallowRef({
            component: null,
            position: { top: 0, left: 0 },
          }),
        },
      },
    })

    expect(document.querySelector('dialog.context-menu')).toBeTruthy()
  })

  it('opens when options.component is set', async () => {
    const options = shallowRef<any>({
      component: null,
      position: { top: 0, left: 0 },
    })

    h.render(Component, {
      global: {
        provide: {
          [ContextMenuKey as symbol]: options,
        },
      },
    })

    options.value = {
      component: { template: '<div>Menu Content</div>' },
      position: { top: 100, left: 200 },
    }

    await h.tick(2)

    expect(HTMLDialogElement.prototype.showModal).toHaveBeenCalled()
  })

  it('closes when options.component is cleared', async () => {
    const options = shallowRef<any>({
      component: { template: '<div>Menu</div>' },
      position: { top: 100, left: 200 },
    })

    h.render(Component, {
      global: {
        provide: {
          [ContextMenuKey as symbol]: options,
        },
      },
    })

    await h.tick()

    options.value = {
      component: null,
      position: { top: 0, left: 0 },
    }

    await h.tick()

    expect(HTMLDialogElement.prototype.close).toHaveBeenCalled()
  })

  it('applies extra class', () => {
    h.render(Component, {
      props: { extraClass: 'my-custom-class' },
      global: {
        provide: {
          [ContextMenuKey as symbol]: shallowRef({
            component: null,
            position: { top: 0, left: 0 },
          }),
        },
      },
    })

    expect(document.querySelector('dialog.my-custom-class')).toBeTruthy()
  })
})
