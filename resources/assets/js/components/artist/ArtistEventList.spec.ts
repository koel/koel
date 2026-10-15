import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { screen, waitFor } from '@testing-library/vue'
import { artistStore } from '@/stores/artistStore'
import Component from './ArtistEventList.vue'

describe('artistEventList.vue', () => {
  const h = createHarness()

  const renderComponent = async (artist?: Artist, events?: LiveEvent[], nearby = true) => {
    events = events ?? h.factory('live-event').make(5)
    artist = artist ?? h.factory('artist').make()

    const fetchEventsMock = h.mock(artistStore, 'fetchEvents').mockResolvedValueOnce({ events, nearby })

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

  it('says the events are elsewhere when there are none nearby', async () => {
    await renderComponent(undefined, undefined, false)

    await waitFor(() => screen.getByTestId('events-elsewhere'))
    expect(screen.queryByTestId('no-events')).toBeNull()
  })

  it('shows no empty state alongside nearby events', async () => {
    await renderComponent()

    await waitFor(() => expect(screen.queryAllByTestId('event-item').length).toBe(5))
    expect(screen.queryByTestId('no-events')).toBeNull()
    expect(screen.queryByTestId('events-elsewhere')).toBeNull()
  })

  it('shows the empty state when there are no events anywhere', async () => {
    await renderComponent(undefined, [], false)

    await waitFor(() => screen.getByTestId('no-events'))
  })
})
