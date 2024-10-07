import { expect, it, vi } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import { screen, waitFor } from '@testing-library/vue'
import { playlistStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import PlaylistScreen from './PlaylistScreen.vue'
import { useSongList } from '@/composables'
import { ref } from 'vue'

let playlist: Playlist

new class extends UnitTestCase {  
  protected test () {
    it('renders the playlist', async () => {
      await this.renderComponent(factory('song', 10))

      await waitFor(() => {
        screen.getByTestId('song-list')
        expect(screen.queryByTestId('screen-empty-state')).toBeNull()
      })
    })

    it('displays the empty state if playlist is empty', async () => {
      await this.renderComponent([])

      await waitFor(() => {
        screen.getByTestId('screen-empty-state')
        expect(screen.queryByTestId('song-list')).toBeNull()
      })
    })

    it('downloads the playlist', async () => {
      const downloadMock = this.mock(downloadService, 'fromPlaylist')
      await this.renderComponent(factory('song', 10))

      await this.tick(2)
      await this.user.click(screen.getByRole('button', { name: 'Download All' }))

      await waitFor(() => expect(downloadMock).toHaveBeenCalledWith(playlist))
    })

    it('deletes the playlist', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      await this.renderComponent([])

      await this.user.click(screen.getByRole('button', { name: 'Delete this playlist' }))

      await waitFor(() => expect(emitMock).toHaveBeenCalledWith('PLAYLIST_DELETE', playlist))
    })

    it('refreshes the playlist', async () => {
      const { fetchMock } = await this.renderComponent([])

      await this.user.click(screen.getByRole('button', { name: 'Refresh' }))

      expect(fetchMock).toHaveBeenCalledWith(playlist, true)
    })

    it('overwrites localStorage when overwriteSort is true', async () => {
      // Mock localStorage setItem
      vi.mock('@/composables', () => ({
        useSongList: vi.fn()
      }));

      const setItemMock = vi.spyOn(localStorage, 'setItem');

      // Mock baseSort function from useSongList
      const baseSortMock = vi.fn();

      // Mock the return value of useSongList
      vi.spyOn(useSongList, 'default').mockReturnValue({
        sort: baseSortMock,
        songs: ref([]), // Provide necessary properties
        config: {},
        // Add other destructured properties if needed
      });

      // Render the PlaylistScreen component
      await this.render(PlaylistScreen);

      // Call the sort function (you will need to adjust this part to actually call the sort method)
      const sortField = 'title'; // Example field
      const sortOrder = 'asc'; // Example order
      const overwriteSort = true; // Set to true to test overwriting localStorage
      const sortMethod = useSongList(ref<Playable[] | CollaborativeSong[]>([]), { type: 'Playlist' }).sort; // Get the sort method from useSongList
      sortMethod(sortField, sortOrder, overwriteSort);

      // Assert localStorage.setItem was called
      expect(setItemMock).toHaveBeenCalledWith('koelPlaylistSortDefault', JSON.stringify({
        field: sortField,
        order: sortOrder
      }));

      // Assert baseSort was called with correct parameters
      expect(baseSortMock).toHaveBeenCalledWith(sortField, sortOrder);

      // Clean up mocks
      setItemMock.mockRestore();
      baseSortMock.mockRestore();
    });
  }

    

    // it('sorts by default when no localStorage value is present', async () => {
    //   // Mock localStorage getItem to return null
    //   const getItemMock = vi.spyOn(localStorage, 'getItem').mockReturnValue(null);

    //   // Mock baseSort from useSongList
    //   const baseSortMock = vi.fn();
    //   vi.spyOn(useSongList, 'default').mockReturnValue({
    //     sort: baseSortMock,
    //     config: {},
    //     songs: ref([]),
    //     // Add other destructured properties if needed
    //   });

    //   // Render the PlaylistScreen component
    //   await this.render(PlaylistScreen);

    //   // Simulate sorting
    //   await this.user.click(screen.getByRole('button', { name: 'Sort' }));

    //   // Assert that the sorting logic was applied with default values
    //   expect(baseSortMock).toHaveBeenCalledWith('defaultField', 'asc'); // Modify with actual default values

    //   // Check that localStorage.getItem was called
    //   expect(getItemMock).toHaveBeenCalledWith('koelPlaylistSortDefault');

    //   getItemMock.mockRestore();
    //   baseSortMock.mockRestore();
    // });

    // it('sorts using localStorage when value is present', async () => {
    //   // Mock localStorage getItem to return a valid object
    //   const getItemMock = vi.spyOn(localStorage, 'getItem').mockReturnValue(JSON.stringify({
    //     field: 'artist',
    //     order: 'desc'
    //   }));

    //   // Mock baseSort from useSongList
    //   const baseSortMock = vi.fn();
    //   vi.spyOn(useSongList, 'default').mockReturnValue({
    //     sort: baseSortMock,
    //     config: {},
    //     songs: ref([]),
    //     // Add other destructured properties if needed
    //   });

    //   // Render the PlaylistScreen component
    //   await this.render(PlaylistScreen);

    //   // Simulate sorting
    //   await this.user.click(screen.getByRole('button', { name: 'Sort' }));

    //   // Assert that baseSort was called with the values from localStorage
    //   expect(baseSortMock).toHaveBeenCalledWith('artist', 'desc');
      
    //   getItemMock.mockRestore();
    //   baseSortMock.mockRestore();
    // });

    // it('overwrites localStorage when overwriteSort is true', async () => {
    //   // Mock localStorage setItem
    //   const setItemMock = vi.spyOn(localStorage, 'setItem');

    //   // Mock baseSort from useSongList
    //   const baseSortMock = vi.fn();
    //   vi.spyOn(useSongList, 'default').mockReturnValue({
    //     sort: baseSortMock,
    //     config: {},
    //     songs: ref([]),
    //     // Add other destructured properties if needed
    //   });

    //   // Render the PlaylistScreen component
    //   await this.render(PlaylistScreen);

    //   // Simulate sorting with overwrite
    //   await this.user.click(screen.getByRole('button', { name: 'Sort' }));

    //   // Assert localStorage.setItem was called
    //   expect(setItemMock).toHaveBeenCalledWith('koelPlaylistSortDefault', JSON.stringify({
    //     field: 'album',
    //     order: 'asc' // Example values
    //   }));

    //   setItemMock.mockRestore();
    //   baseSortMock.mockRestore();
    // });
  

  private async renderComponent (songs: Playable[]) {
    playlist = playlist || factory('playlist')
    this.be(factory('user', { id: playlist.user_id }))

    playlistStore.init([playlist])
    playlist.playables = songs

    const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue(songs)

    const rendered = this.render(PlaylistScreen)

    await this.router.activateRoute({
      path: `playlists/${playlist.id}`,
      screen: 'Playlist'
    }, { id: playlist.id })

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith(playlist, false))

    return { rendered, fetchMock }
  }
}
