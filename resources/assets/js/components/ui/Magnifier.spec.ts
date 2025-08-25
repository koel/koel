import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './Magnifier.vue'

describe('magnifier.vue', () => {
  const h = createHarness()

  it('renders and functions', async () => {
    const { html, emitted } = h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Zoom in' }))
    expect(emitted().in).toBeTruthy()

    await h.user.click(screen.getByRole('button', { name: 'Zoom out' }))
    expect(emitted().out).toBeTruthy()

    expect(html()).toMatchSnapshot()
  })
})
