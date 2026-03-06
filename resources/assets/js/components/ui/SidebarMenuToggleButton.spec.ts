import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './SidebarMenuToggleButton.vue'

describe('sidebarMenuToggleButton.vue', () => {
  const h = createHarness()

  it('emits TOGGLE_SIDEBAR on click', async () => {
    const emitMock = h.mock(eventBus, 'emit')

    const { container } = h.render(Component)
    await h.user.click(container.querySelector('button')!)

    expect(emitMock).toHaveBeenCalledWith('TOGGLE_SIDEBAR')
  })
})
