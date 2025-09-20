import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import Component from './CreditsBlock.vue'

describe('creditsBlock.vue', () => {
  const h = createHarness()

  it('renders the credits', async () => h.withDemoMode(async () => {
    const getMock = h.mock(http, 'get').mockResolvedValue([
      { name: 'Foo', url: 'https://foo.com' },
      { name: 'Bar', url: 'https://bar.com' },
      { name: 'Something Else', url: 'https://something-else.net' },
    ])

    const { html } = h.render(Component)

    await h.tick(3)
    expect(html()).toMatchSnapshot()
    expect(getMock).toHaveBeenCalledWith('demo/credits')
  }))
})
