import { each } from 'lodash'

import { queueStore, playlistStore, favoriteStore } from '../stores'

/**
 * Includes the methods triggerable on a song (context) menu.
 * Each component including this mixin must have a `songs` array as either data, prop, or computed.
 * Note that for some components, some of the methods here may not be applicable, or overridden,
 * for example close() and open().
 */
export default {
  data () {
    return {
      shown: false,
      top: 0,
      left: 0
    }
  },

  methods: {
    open () {},

    /**
     * Close all submenus.
     */
    close () {
      each(Array.from(this.$el.querySelectorAll('.submenu')), el => {
        el.style.display = 'none'
      })
      this.shown = false
    },

    /**
     * Queue select songs after the current song.
     */
    queueSongsAfterCurrent () {
      queueStore.queueAfterCurrent(this.songs)
      this.close()
    },

    /**
     * Queue selected songs to bottom of queue.
     */
    queueSongsToBottom () {
      queueStore.queue(this.songs)
      this.close()
    },

    /**
     * Queue selected songs to top of queue.
     */
    queueSongsToTop () {
      queueStore.queue(this.songs, false, true)
      this.close()
    },

    /**
     * Add the selected songs into Favorites.
     */
    addSongsToFavorite () {
      favoriteStore.like(this.songs)
      this.close()
    },

    /**
     * Add the selected songs into the chosen playlist.
     *
     * @param {Object} playlist The playlist.
     */
    addSongsToExistingPlaylist (playlist) {
      playlistStore.addSongs(playlist, this.songs)
      this.close()
    }
  }
}
