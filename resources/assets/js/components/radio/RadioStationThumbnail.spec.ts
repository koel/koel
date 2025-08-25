import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './RadioStationThumbnail.vue'

describe('radioStationThumbnail.vue', () => {
  const h = createHarness()

  const renderComponent = (station?: RadioStation) => {
    station = station || h.factory('radio-station', {
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

  it('emits the clicked event', async () => {
    const { emitted } = renderComponent()

    await h.user.click(screen.getByRole('button'))
    expect(emitted().clicked).not.toBeNull()
  })
})
