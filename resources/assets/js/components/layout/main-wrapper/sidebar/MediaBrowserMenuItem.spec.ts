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

    await h.router.activateRoute({
      path: '_',
      screen: 'MediaBrowser',
    }, {
      path: 'foo/bar',
    })

    await h.tick(2)

    expect(html()).toMatchSnapshot()
  })
})
