import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { eventBus } from '@/utils/eventBus'
import { commonStore } from '@/stores/commonStore'
import Component from './SidebarYourLibrarySection.vue'

describe('sidebarYourLibrarySection.vue', () => {
  const h = createHarness()

  const renderWith = (usesPodcasts: boolean, usesRadio: boolean) => {
    commonStore.state.uses_podcasts = usesPodcasts
    commonStore.state.uses_radio = usesRadio

    return h.render(Component)
  }

  it('shows podcasts and radio when both are enabled', () => {
    renderWith(true, true)

    screen.getByText('Podcasts')
    screen.getByText('Radio')
  })

  it('hides podcasts when disabled', () => {
    renderWith(false, true)

    expect(screen.queryByText('Podcasts')).toBeNull()
    screen.getByText('Radio')
  })

  it('hides radio when disabled', () => {
    renderWith(true, false)

    expect(screen.queryByText('Radio')).toBeNull()
    screen.getByText('Podcasts')
  })

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
