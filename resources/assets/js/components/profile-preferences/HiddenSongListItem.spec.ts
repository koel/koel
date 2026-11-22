import { describe, expect, it } from 'vite-plus/test'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import Component from './HiddenSongListItem.vue'

describe('hiddenSongListItem.vue', () => {
  const h = createHarness()

  it('unhides the song', async () => {
    const song = h.factory('song').make({ hidden: true })
    const unhideMock = h.mock(playableStore, 'unhideSongs').mockResolvedValue(undefined)

    const { emitted } = h.render(Component, {
      props: {
        song,
      },
    })

    await h.user.click(screen.getByRole('button', { name: 'Unhide' }))

    expect(unhideMock).toHaveBeenCalledWith([song])
    expect(emitted().unhidden[0]).toEqual([song])
  })
})
