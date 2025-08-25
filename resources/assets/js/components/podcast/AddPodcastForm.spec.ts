import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { podcastStore } from '@/stores/podcastStore'
import Component from './AddPodcastForm.vue'

describe('addPodcastForm.vue', () => {
  const h = createHarness()

  it('adds a new podcast', async () => {
    const storeMock = h.mock(podcastStore, 'store').mockResolvedValue(h.factory('podcast'))
    h.render(Component)
    await h.type(screen.getByPlaceholderText('https://example.com/feed.xml'), 'https://foo.bar/feed.xml')
    await h.user.click(screen.getByRole('button', { name: 'Save' }))
    expect(storeMock).toHaveBeenCalledWith('https://foo.bar/feed.xml')
  })
})
