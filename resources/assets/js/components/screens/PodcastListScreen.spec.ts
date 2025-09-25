import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { podcastStore } from '@/stores/podcastStore'
import Component from './PodcastListScreen.vue'

describe('podcastListScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async () => {
    const rendered = h.render(Component, {
      global: {
        stubs: {
          PodcastItem: h.stub('podcast-item'),
        },
      },
    })

    h.visit('/podcasts')
    await h.tick()

    return rendered
  }

  it('renders', async () => {
    const fetchMock = h.mock(podcastStore, 'fetchAll')
    podcastStore.state.podcasts = h.factory('podcast', 9)

    await renderComponent()

    expect(screen.getAllByTestId('podcast-item')).toHaveLength(9)
    expect(fetchMock).toHaveBeenCalled()
  })

  it('shows a message when there are no podcasts', async () => {
    h.mock(podcastStore, 'fetchAll')
    podcastStore.state.podcasts = []
    await renderComponent()

    await waitFor(() => screen.getByTestId('screen-empty-state'))
  })

  it('shows all or only favorites upon toggling the button', async () => {
    podcastStore.state.podcasts = [
      ...h.factory('podcast', 3, { favorite: true }),
      ...h.factory('podcast', 6, { favorite: false }),
    ]

    h.mock(podcastStore, 'fetchAll')

    await renderComponent()
    expect(screen.getAllByTestId('podcast-item')).toHaveLength(9)

    await h.user.click(screen.getByRole('button', { name: 'Show favorites only' }))
    await waitFor(() => expect(screen.getAllByTestId('podcast-item')).toHaveLength(3))

    await h.user.click(screen.getByRole('button', { name: 'Show all' }))
    await waitFor(() => expect(screen.getAllByTestId('podcast-item')).toHaveLength(9))
  })
})
