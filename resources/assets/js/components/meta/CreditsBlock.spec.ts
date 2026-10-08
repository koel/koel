import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import Component from './CreditsBlock.vue'

describe('creditsBlock.vue', () => {
  const h = createHarness()

  it('lists the demo credits sorted by name', async () =>
    h.withDemoMode(async () => {
      const getMock = h.mock(http, 'get').mockResolvedValue([
        { name: 'Foo', url: 'https://foo.com' },
        { name: 'Bar', url: 'https://bar.com' },
        { name: 'Something Else', url: 'https://something-else.net' },
      ])

      h.render(Component)
      await h.tick(3)

      expect(getMock).toHaveBeenCalledWith('demo/credits')
      expect(screen.getAllByRole('link').map(link => link.getAttribute('href'))).toEqual([
        'https://bar.com',
        'https://foo.com',
        'https://something-else.net',
      ])
    }))
})
