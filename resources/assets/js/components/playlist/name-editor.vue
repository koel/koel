<template>
  <input
    type="text"
    name="name"
    @keyup.esc="cancel"
    @keyup.enter="update"
    @blur="update"
    v-model="mutatedPlaylist.name"
    v-koel-focus
    required
    data-testid="inline-playlist-name-input"
  >
</template>

<script lang="ts">
import Vue, { PropOptions } from 'vue'
import { playlistStore } from '@/stores'

export default Vue.extend({
  props: {
    playlist: {
      type: Object,
      required: true
    } as PropOptions<Playlist>
  },

  data: () => ({
    mutatedPlaylist: null as unknown as Playlist,
    updating: false
  }),

  methods: {
    async update (): Promise<void> {
      this.mutatedPlaylist.name = this.mutatedPlaylist.name.trim()

      if (!this.mutatedPlaylist.name) {
        this.cancel()
        return
      }

      if (this.mutatedPlaylist.name === this.playlist.name) {
        this.cancel()
        return
      }

      // prevent duplicate updating from Enter and Blur
      if (this.updating) {
        return
      }

      this.updating = true

      await playlistStore.update(this.mutatedPlaylist)
      this.$emit('updated', this.mutatedPlaylist)
    },

    cancel (): void {
      this.$emit('cancelled')
    }
  },

  created (): void {
    this.mutatedPlaylist = Object.assign({}, this.playlist)
  }
})
</script>
