import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { screen } from '@testing-library/vue'
import Component from './EmbedWidgetTrackList.vue'

describe('embedWidgetTrackList.vue', async () => {
  const h = createHarness()

  it('renders the track list', () => {
    h.render(Component, {
      props: {
        playables: h.factory('song', 10),
      },
      global: {
        stubs: {
          TrackItem: h.stub('track-item'),
        },
      },
    })

    expect(screen.queryAllByTestId('track-item')).toHaveLength(10)
  })
})
