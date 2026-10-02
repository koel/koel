import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './SidebarToggleButton.vue'

describe('sidebarToggleButton.vue', () => {
  const h = createHarness()

  it('emits update:modelValue on toggle', async () => {
    const { container, emitted } = h.render(Component, {
      props: { modelValue: true },
    })

    await h.user.click(container.querySelector('input[type="checkbox"]')!)
    expect(emitted()['update:modelValue'][0]).toEqual([false])
  })
})
