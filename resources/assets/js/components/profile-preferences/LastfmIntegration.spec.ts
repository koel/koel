import { screen } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { http } from '@/services/http'
import Component from './LastfmIntegration.vue'

describe('lastfmIntegration.vue', () => {
  const h = createHarness()

  it.each<[boolean, boolean]>([
    [false, false],
    [false, true],
    [true, false],
    [true, true],
  ])(
    'renders proper content with Last.fm integration status %s, current user admin status %s',
    (useLastfm, isAdmin) => {
      commonStore.state.uses_last_fm = useLastfm

      if (isAdmin) {
        h.actingAsAdmin()
      } else {
        h.actingAsUser()
      }

      expect(h.render(Component).html()).toMatchSnapshot()
    },
  )

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
