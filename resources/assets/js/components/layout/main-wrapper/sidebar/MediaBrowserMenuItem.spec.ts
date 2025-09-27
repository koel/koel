import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './MediaBrowserMenuItem.vue'

describe('mediaBrowserMenuItem.vue', () => {
  const h = createHarness()

  it('renders', () => {
    expect(h.render(Component).html()).toMatchSnapshot()
  })

  it('keeps track of the active path', async () => {
    const { html } = h.render(Component)

    h.visit('/browse/foo/bar')

    expect(html()).toMatchSnapshot()
  })
})
