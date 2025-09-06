import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { artistStore } from '@/stores/artistStore'
import Component from './ArtistEventList.vue'

describe('artistEventList.vue', () => {
  const h = createHarness()

  const renderComponent = async (artist?: Artist, events?: LiveEvent[]) => {
    events = events ?? h.factory('live-event', 5)
    artist = artist ?? h.factory('artist')

    const fetchEventsMock = h.mock(artistStore, 'fetchEvents').mockResolvedValueOnce(events)

    const rendered = h.render(Component, {
      props: {
        artist,
      },
      global: {
        stubs: {
          ArtistEventItem: h.stub('event-item'),
        },
      },
    })

    await h.tick()

    expect(fetchEventsMock).toHaveBeenCalledWith(artist)

    return {
      ...rendered,
      artist,
      events,
      fetchEventsMock,
    }
  }

  it('renders the events', async () => {
    const { events } = await renderComponent()
    await waitFor(() => expect(screen.queryAllByTestId('event-item').length).toBe(events.length))
  })
})
