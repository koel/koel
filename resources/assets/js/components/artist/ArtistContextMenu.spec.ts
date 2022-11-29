import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { eventBus } from '@/utils'
import { downloadService, playbackService } from '@/services'
import { commonStore, songStore } from '@/stores'
import ArtistContextMenu from './ArtistContextMenu.vue'
import { screen } from '@testing-library/vue'

let artist: Artist

new class extends UnitTestCase {
  private async renderComponent (_artist?: Artist) {
    artist = _artist || factory<Artist>('artist', {
      name: 'Accept'
    })

    const rendered = this.render(ArtistContextMenu)
    eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', { pageX: 420, pageY: 42 }, artist)
    await this.tick(2)

    return rendered
  }

  protected test () {
    it('renders', async () => expect((await this.renderComponent()).html()).toMatchSnapshot())

    it('plays all', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      await this.renderComponent()
      await screen.getByText('Play All').click()
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs)
    })

    it('shuffles all', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      await this.renderComponent()
      await screen.getByText('Shuffle All').click()
      await this.tick()

      expect(fetchMock).toHaveBeenCalledWith(artist)
      expect(playMock).toHaveBeenCalledWith(songs, true)
    })

    it('downloads', async () => {
      const mock = this.mock(downloadService, 'fromArtist')

      await this.renderComponent()
      await screen.getByText('Download').click()

      expect(mock).toHaveBeenCalledWith(artist)
    })

    it('does not have an option to download if downloading is disabled', async () => {
      commonStore.state.allow_download = false
      await this.renderComponent()

      expect(screen.queryByText('Download')).toBeNull()
    })

    it('goes to artist', async () => {
      const mock = this.mock(this.router, 'go')
      await this.renderComponent()

      await screen.getByText('Go to Artist').click()

      expect(mock).toHaveBeenCalledWith(`artist/${artist.id}`)
    })

    it('does not have an option to download or go to Unknown Artist', async () => {
      await this.renderComponent(factory.states('unknown')<Artist>('artist'))

      expect(screen.queryByText('Go to Artist')).toBeNull()
      expect(screen.queryByText('Download')).toBeNull()
    })

    it('does not have an option to download or go to Various Artist', async () => {
      await this.renderComponent(factory.states('various')<Artist>('artist'))

      expect(screen.queryByText('Go to Artist')).toBeNull()
      expect(screen.queryByText('Download')).toBeNull()
    })
  }
}
