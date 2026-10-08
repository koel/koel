import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useBranding } from '@/composables/useBranding'
import Component from './RadioStationThumbnail.vue'

describe('radioStationThumbnail.vue', () => {
  const h = createHarness()

  const renderComponent = (logo: string | null) =>
    h.render(Component, {
      props: {
        station: h.factory('radio-station').make({ logo }),
      },
    })

  it('shows the station logo', () => {
    renderComponent('https://test/beet.jpg')

    expect(screen.getByRole('img').getAttribute('src')).toBe('https://test/beet.jpg')
  })

  it('falls back to the default cover without a logo', () => {
    renderComponent(null)

    expect(screen.getByRole('img').getAttribute('src')).toBe(useBranding().cover)
  })
})
