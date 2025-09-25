import { describe, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { eventBus } from '@/utils/eventBus'
import Component from './Sidebar.vue'

const standardItems = [
  'All Songs',
  'Albums',
  'Artists',
  'Genres',
  'Favorites',
  'Recently Played',
]

const adminItems = [...standardItems, 'Users', 'Upload', 'Settings']

describe('sidebar.vue', () => {
  const h = createHarness()

  it('shows the standard items', () => {
    h.actingAsUser().render(Component)
    standardItems.forEach(label => screen.getByText(label))
  })

  it('shows administrative items', () => {
    h.actingAsAdmin().render(Component)
    adminItems.forEach(label => screen.getByText(label))
  })

  it('shows the YouTube sidebar item on demand', async () => {
    commonStore.state.uses_you_tube = true
    h.render(Component)

    eventBus.emit('PLAY_YOUTUBE_VIDEO', { id: '123', title: 'A Random Video' })
    await h.tick()

    screen.getByText('A Random Video')
  })
})
