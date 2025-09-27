import { screen, waitFor } from '@testing-library/vue'
import type { Mock } from 'vitest'
import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore as episodeStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import PlayableContextMenu from '@/components/playable/PlayableContextMenu.vue'
import Component from './EpisodeScreen.vue'

describe('episodeScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async (episode?: Episode) => {
    episode = episode || h.factory('episode')

    const resolveEpisodeMock = h.mock(episodeStore, 'resolve').mockResolvedValue(episode)

    const rendered = h.visit(`episodes/${episode.id}`).render(Component)

    await waitFor(() => {
      expect(resolveEpisodeMock).toHaveBeenCalledWith(episode.id)
    })

    return {
      ...rendered,
      episode,
      resolveEpisodeMock,
    }
  }

  it('has a Favorite button if episode is favorite', async () => {
    const { episode } = await renderComponent(h.factory('episode', { favorite: true }))
    const favoriteMock = h.mock(episodeStore, 'toggleFavorite')

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'Undo Favorite' }))
      expect(favoriteMock).toHaveBeenCalledWith(episode)
    })
  })

  it('does not have a Favorite button if episode is not favorite', async () => {
    await renderComponent(h.factory('episode', { favorite: false }))
    expect(screen.queryByRole('button', { name: 'Favorite' })).toBeNull()
  })

  it('requests Actions menu', async () => {
    vi.mock('@/composables/useContextMenu')
    const { openContextMenu } = useContextMenu()
    const { episode } = await renderComponent()

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      await assertOpenContextMenu(openContextMenu as Mock, PlayableContextMenu, { playables: [episode] })
    })
  })
})
