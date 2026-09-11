import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { userStore } from '@/stores/userStore'
import Component from './ListenBrainzIntegration.vue'

describe('listenBrainzIntegration.vue', () => {
  const h = createHarness()

  const renderForConnectedUser = (connected: boolean) => {
    h.actingAsUser()
    userStore.state.current.preferences.listenbrainz_token = connected ? 'my-token' : undefined

    return h.render(Component)
  }

  it('offers to connect when no token is stored', () => {
    renderForConnectedUser(false)

    screen.getByRole('button', { name: 'Connect' })
    expect(screen.queryByRole('button', { name: 'Disconnect' })).toBeNull()
  })

  it('offers to disconnect when a token is stored', () => {
    renderForConnectedUser(true)

    screen.getByRole('button', { name: 'Disconnect' })
    expect(screen.queryByRole('button', { name: 'Connect' })).toBeNull()
  })

  it('submits the token and switches to the connected state', async () => {
    const postMock = h.mock(http, 'post').mockResolvedValue(null)
    renderForConnectedUser(false)

    await h.user.type(screen.getByPlaceholderText('ListenBrainz user token'), 'my-token')
    await h.user.click(screen.getByRole('button', { name: 'Connect' }))

    expect(postMock).toHaveBeenCalledWith('listenbrainz/token', { token: 'my-token' })
    await waitFor(() => screen.getByRole('button', { name: 'Disconnect' }))
    expect(userStore.state.current.preferences.listenbrainz_token).toBe('my-token')
  })

  it('disconnects and switches back to the form', async () => {
    const deleteMock = h.mock(http, 'delete').mockResolvedValue(null)
    renderForConnectedUser(true)

    await h.user.click(screen.getByRole('button', { name: 'Disconnect' }))

    expect(deleteMock).toHaveBeenCalledWith('listenbrainz/token')
    await waitFor(() => screen.getByRole('button', { name: 'Connect' }))
    expect(userStore.state.current.preferences.listenbrainz_token).toBeUndefined()
  })
})
