import Router from '@/router'
import { describe, expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils/eventBus'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/QueuePlaybackService'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { resourcePermissionService } from '@/services/resourcePermissionService'
import Component from './AlbumContextMenu.vue'

describe('albumContextMenu.vue', () => {
  const h = createHarness()

  const renderComponent = async (album?: Album) => {
    h.mock(resourcePermissionService, 'check').mockReturnValue(true)

    album = album || h.factory('album', {
      name: 'IV',
      favorite: false,
    })

    const rendered = h.beAdmin().render(Component)
    eventBus.emit('ALBUM_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 } as MouseEvent, album)
    await h.tick(2)

    return {
      ...rendered,
      album,
    }
  }

  it('renders', async () => expect((await renderComponent()).html()).toMatchSnapshot())

  it('plays all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { album } = await renderComponent()
    await h.user.click(screen.getByText('Play All'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(album)
    expect(playMock).toHaveBeenCalledWith(songs)
  })

  it('shuffles all', async () => {
    h.createAudioPlayer()

    const songs = h.factory('song', 10)
    const fetchMock = h.mock(playableStore, 'fetchSongsForAlbum').mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')

    const { album } = await renderComponent()
    await h.user.click(screen.getByText('Shuffle All'))
    await h.tick()

    expect(fetchMock).toHaveBeenCalledWith(album)
    expect(playMock).toHaveBeenCalledWith(songs, true)
  })

  it('downloads', async () => {
    const downloadMock = h.mock(downloadService, 'fromAlbum')
    const { album } = await renderComponent()

    await h.user.click(screen.getByText('Download'))

    expect(downloadMock).toHaveBeenCalledWith(album)
  })

  it('does not have an option to download if downloading is disabled', async () => {
    commonStore.state.allows_download = false
    await renderComponent()

    expect(screen.queryByText('Download')).toBeNull()
  })

  it('goes to album', async () => {
    const mock = h.mock(Router, 'go')
    const { album } = await renderComponent()

    await h.user.click(screen.getByText('Go to Album'))

    expect(mock).toHaveBeenCalledWith(`/#/albums/${album.id}`)
  })

  it('does not have an option to download or go to Unknown Album and Artist', async () => {
    await renderComponent(factory.states('unknown')('album'))

    expect(screen.queryByText('Go to Album')).toBeNull()
    expect(screen.queryByText('Go to Artist')).toBeNull()
    expect(screen.queryByText('Download')).toBeNull()
  })

  it('goes to artist', async () => {
    const mock = h.mock(Router, 'go')
    const { album } = await renderComponent()

    await h.user.click(screen.getByText('Go to Artist'))

    expect(mock).toHaveBeenCalledWith(`/#/artists/${album.artist_id}`)
  })

  it('requests edit form', async () => {
    const { album } = await renderComponent()

    // for the "Edit…" menu item to show up
    await h.tick(2)

    const emitMock = h.mock(eventBus, 'emit')
    await h.user.click(screen.getByText('Edit…'))

    expect(emitMock).toHaveBeenCalledWith('MODAL_SHOW_EDIT_ALBUM_FORM', album)
  })
})
