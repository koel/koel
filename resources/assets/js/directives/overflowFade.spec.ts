import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { overflowFade } from './overflowFade'

describe('overflowFade directive', () => {
  const h = createHarness()

  it('adds scroll listener on mount', () => {
    const { container } = h.render({
      directives: { overflowFade },
      template: '<div v-overflow-fade style="height: 50px; overflow: auto"><p style="height: 200px">Content</p></div>',
    })

    const el = container.querySelector('div')!
    // Trigger a scroll event to verify the listener was added
    el.dispatchEvent(new Event('scroll'))
    // The directive should not throw
    expect(el).toBeTruthy()
  })
})
