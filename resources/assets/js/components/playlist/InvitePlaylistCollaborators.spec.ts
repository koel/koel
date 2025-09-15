import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import Component from './InvitePlaylistCollaborators.vue'

describe('invitePlaylistCollaborators.vue', () => {
  const h = createHarness()

  it('works', async () => {
    h.mock(playlistCollaborationService, 'createInviteLink').mockResolvedValue('http://localhost:3000/invite/1234')
    const playlist = h.factory('playlist')

    h.render(Component, {
      props: {
        playlist,
      },
    })

    await h.user.click(screen.getByText('Invite'))

    await waitFor(async () => {
      expect(navigator.clipboard.writeText).toHaveBeenCalledWith('http://localhost:3000/invite/1234')
    })
  })
})
