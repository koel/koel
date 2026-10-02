import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { newTab } from './newTab'

describe('newTab directive', () => {
  const h = createHarness()

  it('sets target=_blank on anchor tags within element', () => {
    const { container } = h.render({
      directives: { newTab },
      template: '<div v-new-tab><a href="https://example.com">Link</a></div>',
    })

    const anchor = container.querySelector('a')!
    expect(anchor.getAttribute('target')).toBe('_blank')
  })
})
