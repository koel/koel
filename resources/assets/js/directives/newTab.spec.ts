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

  it('sets target=_blank on multiple anchors', () => {
    const { container } = h.render({
      directives: { newTab },
      template: `
        <div v-new-tab>
          <a href="https://a.com">A</a>
          <a href="https://b.com">B</a>
        </div>
      `,
    })

    const anchors = container.querySelectorAll('a')
    anchors.forEach(a => expect(a.getAttribute('target')).toBe('_blank'))
  })

  it('handles elements without anchors', () => {
    const { container } = h.render({
      directives: { newTab },
      template: '<div v-new-tab><span>No links</span></div>',
    })

    expect(container.querySelectorAll('a')).toHaveLength(0)
  })
})
