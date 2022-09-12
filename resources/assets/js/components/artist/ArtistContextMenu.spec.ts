import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { downloadService, playbackService } from '@/services'
import { commonStore, songStore } from '@/stores'
import router from '@/router'
import ArtistContextMenu from './ArtistContextMenu.vue'

let artist: Artist

new class extends UnitTestCase {
  private async renderComponent (_artist?: Artist) {
    artist = _artist || factory<Artist>('artist', {
      name: 'Accept',
      play_count: 30,
      song_count: 10,
      length: 123
    })

    const rendered = this.render(ArtistContextMenu)
    eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 }, artist)
    await this.tick(2)

    return rendered
  }

  protected test () {
    it('renders', async () => {
      const { html } = await this.renderComponent()
      expect(html()).toMatchSnapshot()
    })

    it('plays all', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { getByText } = await this.renderComponent()
      await getByText('Play All').click()
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs)
    })

    it('shuffles all', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      const { getByText } = await this.renderComponent()
      await getByText('Shuffle All').click()
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs, true)
    })

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromArtist')

      const { getByText } = await this.renderComponent()
      await getByText('Download').click()

      expect(mock).toHaveBeenCalledWith(artist)
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allow_download = false
      const { queryByText } = await this.renderComponent()

      expect(queryByText('Download')).toBeNull()
    })

    it('goes to artist', async () => {
      const mock = this.mock(router, 'go')
      const { getByText } = await this.renderComponent()

      await getByText('Go to Artist').click()

      expect(mock).toHaveBeenCalledWith(`artist/${artist.id}`)
    })

    it('does not have an option to download or go to Unknown Artist', async () => {
      const { queryByTestId } = await this.renderComponent(factory.states('unknown')<Artist>('artist'))

      expect(queryByTestId('view-artist')).toBeNull()
      expect(queryByTestId('download')).toBeNull()
    })

    it('does not have an option to download or go to Various Artist', async () => {
      const { queryByTestId } = await this.renderComponent(factory.states('various')<Artist>('artist'))

      expect(queryByTestId('view-artist')).toBeNull()
      expect(queryByTestId('download')).toBeNull()
    })
  }
}
