import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { encyclopediaService } from '@/services/encyclopediaService'
import Component from './ArtistInfo.vue'

describe('artistInfo.vue', () => {
  const h = createHarness()

  const renderComponent = async (mode: EncyclopediaDisplayMode = 'aside', info?: ArtistInfo) => {
    commonStore.state.uses_last_fm = true
    info = info ?? h.factory('artist-info')
    const artist = h.factory('artist', { name: 'Led Zeppelin' })

    const fetchMock = h.mock(encyclopediaService, 'fetchForArtist').mockResolvedValue(info)

    const rendered = h.render(Component, {
      props: {
        artist,
        mode,
      },
      global: {
        stubs: {
          ArtistThumbnail: h.stub('thumbnail'),
        },
      },
    })

    await h.tick(1)
    expect(fetchMock).toHaveBeenCalledWith(artist)

    return {
      ...rendered,
      artist,
    }
  }

  it.each<[EncyclopediaDisplayMode]>([['aside'], ['full']])('renders in %s mode', async mode => {
    await renderComponent(mode)

    if (mode === 'aside') {
      screen.getByTestId('thumbnail')
    } else {
      expect(screen.queryByTestId('thumbnail')).toBeNull()
    }

    expect(screen.getByTestId('artist-info').classList.contains(mode)).toBe(true)
  })
})
