import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import Component from './AcceptPlaylistCollaborationInvite.vue'

const goMock = vi.fn()
const urlMock = vi.fn((name: string, params?: Record<string, any>) => {
  if (params?.id) return `/#/${name}/${params.id}`
  return `/#/${name}`
})

vi.mock('@/composables/useRouter', () => ({
  useRouter: () => ({
    go: goMock,
    url: urlMock,
    getRouteParam: () => 'invite-token-123',
  }),
}))

describe('acceptPlaylistCollaborationInvite.vue', () => {
  const h = createHarness()

  it('accepts invite and redirects on mount', async () => {
    const playlist = h.factory('playlist')
    h.mock(playlistCollaborationService, 'acceptInvite').mockResolvedValue(playlist)

    h.render(Component)
    await h.tick()

    expect(playlistCollaborationService.acceptInvite).toHaveBeenCalledWith('invite-token-123')
    expect(goMock).toHaveBeenCalled()
  })
})
