import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import Component from './SidebarYourLibrarySection.vue'

describe('sidebarYourLibrarySection.vue', () => {
  const h = createHarness()

  it('shows YouTube item if a video is played', async () => {
    h.render(Component)
    expect(screen.queryByTestId('youtube')).toBeNull()

    eventBus.emit('PLAY_YOUTUBE_VIDEO', {
      id: 'video-id',
      title: 'Another One Bites the Dust',
    })

    await h.tick()
    screen.getByTestId('youtube')
    screen.getByText('Another One Bites the Dust')
  })
})
