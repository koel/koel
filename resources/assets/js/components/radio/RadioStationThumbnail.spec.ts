import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './RadioStationThumbnail.vue'

describe('radioStationThumbnail.vue', () => {
  const h = createHarness()

  const renderComponent = (station?: RadioStation) => {
    station =
      station ||
      h.factory('radio-station').make({
        name: 'Beethoven Goes Metal',
        logo: 'https://test/beet.jpg',
      })

    const rendered = h.render(Component, {
      props: {
        station,
      },
    })

    return {
      ...rendered,
      station,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())
})
