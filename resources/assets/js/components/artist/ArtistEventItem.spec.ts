import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import ArtistEventItem from './ArtistEventItem.vue'

describe('artistEvent.vue', () => {
  const h = createHarness()

  const renderComponent = (event?: LiveEvent) => {
    event = event ?? h.factory('live-event')

    const rendered = h.render(ArtistEventItem, {
      props: {
        event,
      },
    })

    return {
      ...rendered,
      event,
    }
  }

  it('renders', () => {
    renderComponent(h.factory('live-event', {
      id: 'foo',
      name: 'Metalfest',
      url: 'https://www.metalfest.com/tix',
      image: 'https://www.metalfest.com/tix/logo.png',
      dates: {
        start: 'Jan 1, 2022 12:00',
        end: 'Jan 1, 2022 13:00',
      },
      venue: {
        name: 'Backstage',
        url: 'https://www.metalfest.com/venue/backstage',
        city: 'Munich',
      },
    }))

    screen.getByText('Metalfest')
    expect(screen.getByRole('link').getAttribute('href')).toBe('https://www.metalfest.com/tix')
  })
})
