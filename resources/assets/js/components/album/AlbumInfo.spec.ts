import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { encyclopediaService } from '@/services/encyclopediaService'
import Component from './AlbumInfo.vue'

describe('albumInfo.vue', () => {
  const h = createHarness()

  const renderComponent = async (mode: EncyclopediaDisplayMode = 'aside', info?: AlbumInfo | null) => {
    commonStore.state.uses_last_fm = true

    if (info === undefined) {
      info = h.factory('album-info').make()
    }

    const album = h.factory('album').make({ name: 'IV' })
    const fetchMock = h.mock(encyclopediaService, 'fetchForAlbum').mockResolvedValue(info)

    const rendered = h.render(Component, {
      props: {
        album,
        mode,
      },
      global: {
        stubs: {
          TrackList: h.stub(),
          AlbumThumbnail: h.stub('thumbnail'),
        },
      },
    })

    await h.tick(1)
    expect(fetchMock).toHaveBeenCalledWith(album)

    return {
      ...rendered,
      album,
    }
  }

  it.each<[EncyclopediaDisplayMode]>([['aside'], ['full']])('renders in %s mode', async mode => {
    await renderComponent(mode)

    screen.getByTestId('album-info-tracks')

    if (mode === 'aside') {
      screen.getByTestId('thumbnail')
    } else {
      expect(screen.queryByTestId('thumbnail')).toBeNull()
    }

    expect(screen.getByTestId('album-info').classList.contains(mode)).toBe(true)
  })

  it('reports that there is no information to show', async () => {
    const info = { ...h.factory('album-info').make({ wiki: { summary: '', full: '<p>&nbsp;</p>' } }), tracks: [] }

    const { emitted } = await renderComponent('full', info)

    expect(emitted().empty).toHaveLength(1)
  })

  it('reports that there is no information when none is found', async () => {
    const { emitted } = await renderComponent('full', null)

    expect(emitted().empty).toHaveLength(1)
  })

  it('does not report information that only has tracks', async () => {
    const { emitted } = await renderComponent('full', h.factory('album-info').make({ wiki: { summary: '', full: '' } }))

    expect(emitted().empty).toBeUndefined()
  })

  it('does not report information that has text', async () => {
    const { emitted } = await renderComponent('full')

    expect(emitted().empty).toBeUndefined()
  })
})
