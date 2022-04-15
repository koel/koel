<template>
  <section id="songResultsWrapper">
    <screen-header>
      Showing Songs for <strong>{{ decodedQ }}</strong>
      <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="meta.songCount">{{ meta.songCount | pluralize('song') }} • {{ meta.totalLength }}</span>
      </template>

      <template v-slot:controls>
        <song-list-controls
          v-if="state.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          :songs="state.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </screen-header>

    <song-list :items="state.songs" type="search-results" ref="songList"/>
  </section>
</template>

<script lang="ts">
import { searchStore } from '@/stores'
import mixins from 'vue-typed-mixins'
import hasSongList from '@/mixins/has-song-list'
import { pluralize } from '@/utils'

export default mixins(hasSongList).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue')
  },

  filters: { pluralize },

  props: {
    q: {
      type: String,
      required: true
    }
  },

  data: () => ({
    state: searchStore.state
  }),

  computed: {
    decodedQ (): string {
      return decodeURIComponent(this.q)
    }
  },

  created () {
    searchStore.resetSongResultState()
    searchStore.songSearch(this.decodedQ)
  }
})
</script>
