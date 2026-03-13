import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './ContextMenuSeparator.vue'

describe('contextMenuSeparator.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component).html()).toMatchSnapshot()
  })
})
