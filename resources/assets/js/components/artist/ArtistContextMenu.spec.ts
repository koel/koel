import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils/eventBus'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { acl } from '@/services/acl'
import Component from './ArtistContextMenu.vue'

describe('artistContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (artist?: Artist) => {
    h.mock(acl, 'checkResourcePermission').mockReturnValue(true)

    artist = artist || h.factory('artist', {
      name: 'Accept',
      favorite: false,
    })

    const rendered = h.render(Component, {
      props: {
        artist,
      },
    })

    return {
      ...rendered,
      artist,
    }
  }

  it('renders', async () => expect((await renderComponent()).html()).toMatchSnapshot())

  it('plays all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { artist } = await renderComponent()
    await screen.getByText('Play All').click()
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(artist)
    expect(playMock).toHaveBeenCalledWith(songs)
  })

  it('shuffles all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForArtist').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { artist } = await renderComponent()
    await screen.getByText('Shuffle All').click()
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(artist)
    expect(playMock).toHaveBeenCalledWith(songs, true)
  })

  it('downloads', async () => {
    const mock = h.mock(downloadService, 'fromArtist')

    const { artist } = await renderComponent()
    await screen.getByText('Download').click()

    expect(mock).toHaveBeenCalledWith(artist)
  })

  it('does not have an option to download if downloading is disabled', async () => {
    commonStore.state.allows_download = false
    await renderComponent()

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('does not have an option to download Unknown Artist', async () => {
    await renderComponent(factory.states('unknown')('artist'))

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('does not have an option to download Various Artist', async () => {
    await renderComponent(factory.states('various')('artist'))
    expect(screen.queryByText('Download')).toBeNull()
  })

  it('requests the embed form', async () => {
    const { artist } = await renderComponent()
    const emitMock = h.mock(eventBus, 'emit')
    await h.user.click(screen.getByText('Embed…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_CREATE_EMBED_FORM', artist)
  })
})
