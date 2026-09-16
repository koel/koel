import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { userStore } from '@/stores/userStore'
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

  it('styles the disconnect button the same way ListenBrainz does', async () => {
    commonStore.state.uses_last_fm = true
    h.actingAsUser()
    userStore.state.current.preferences.lastfm_session_key = 'my-session-key'

    h.render(Component)

    const button = await screen.findByRole('button', { name: 'Disconnect' })

    expect(button.dataset.variant).toBe('ghost')
    expect(button.hasAttribute('bordered')).toBe(true)
  })
})
