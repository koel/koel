import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import Component from './ServicesSettingGroup.vue'

describe('servicesSettingGroup.vue', () => {
  const h = createHarness()

  it('marks each service by whether it is enabled', () => {
    commonStore.state.uses_musicbrainz = true
    commonStore.state.uses_spotify = false

    h.render(Component)

    expect(screen.getByTestId('service-musicbrainz').dataset.enabled).toBe('true')
    expect(screen.getByTestId('service-spotify').dataset.enabled).toBe('false')
  })

  it('leaves Ticketmaster out of the Community edition', () => {
    h.render(Component)
    expect(screen.queryByTestId('service-ticketmaster')).toBeNull()
  })

  it('lists Ticketmaster in the Plus edition', () => {
    h.withPlusEdition(() => {
      h.render(Component)
      screen.getByTestId('service-ticketmaster')
    })
  })
})
