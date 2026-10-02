import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { focus } from './focus'

describe('focus directive', () => {
  const h = createHarness()

  it('calls focus on the element when mounted', () => {
    const focusSpy = vi.spyOn(HTMLElement.prototype, 'focus')

    h.render({
      directives: { focus },
      template: '<input v-focus data-testid="input" />',
    })

    expect(focusSpy).toHaveBeenCalled()
    focusSpy.mockRestore()
  })
})
