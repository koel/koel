import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { hideBrokenIcon } from './hideBrokenIcon'

describe('hideBrokenIcon directive', () => {
  const h = createHarness()

  it('hides image on error event', async () => {
    const { container } = h.render({
      directives: { hideBrokenIcon },
      template: '<img v-hide-broken-icon src="" data-testid="img" />',
    })

    const img = container.querySelector('img')!
    img.dispatchEvent(new Event('error'))

    expect(img.style.visibility).toBe('hidden')
  })

  it('renders the image element', () => {
    const { container } = h.render({
      directives: { hideBrokenIcon },
      template: '<img v-hide-broken-icon src="valid.png" />',
    })

    expect(container.querySelector('img')).toBeTruthy()
  })
})
