import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Router from '@/router'
import Component from './MediaBrowserMenuItem.vue'

describe('mediaBrowserMenuItem.vue', () => {
  const h = createHarness()

  it('links to the root folder by default', () => {
    h.render(Component)

    expect(screen.getByRole('link').getAttribute('href')).toBe(Router.url('media-browser'))
  })

  it('links to the last visited folder', async () => {
    const folder = crypto.randomUUID()
    h.render(Component)

    h.visit(`/browse/${folder}`)
    await h.tick()

    expect(screen.getByRole('link').getAttribute('href')).toBe(Router.url('media-browser', { folder }))
  })
})
