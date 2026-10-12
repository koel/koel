import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { http } from '@/services/http'
import Component from './LastfmIntegration.vue'

describe('lastfmIntegration.vue', () => {
  const h = createHarness()

  it('offers to connect when Last.fm is enabled', () => {
    commonStore.state.uses_last_fm = true
    h.actingAsUser()
    h.render(Component)

    screen.getByTestId('lastfm-integrated')
  })

  it('points an admin to the setup docs when Last.fm is not enabled', () => {
    commonStore.state.uses_last_fm = false
    h.actingAsAdmin()
    h.render(Component)

    screen.getByTestId('lastfm-admin-instruction')
  })

  it('tells a regular user to ask an admin when Last.fm is not enabled', () => {
    commonStore.state.uses_last_fm = false
    h.actingAsUser()
    h.render(Component)

    screen.getByTestId('lastfm-user-instruction')
  })

  it('opens the Last.fm authorization URL fetched from the API', async () => {
    commonStore.state.uses_last_fm = true
    h.actingAsUser()

    const assignMock = vi.fn()
    const openMock = h.mock(window, 'open').mockReturnValue({ location: { assign: assignMock } } as unknown as Window)
    const getMock = h.mock(http, 'get').mockResolvedValue({ url: 'https://www.last.fm/api/auth/?api_key=foo' })

    h.render(Component)
    await h.user.click(await screen.findByRole('button', { name: 'Reconnect' }))

    expect(openMock).toHaveBeenCalledWith('', '_blank', expect.any(String))
    expect(getMock).toHaveBeenCalledWith('lastfm/authorization-url')
    expect(assignMock).toHaveBeenCalledWith('https://www.last.fm/api/auth/?api_key=foo')
  })
})
