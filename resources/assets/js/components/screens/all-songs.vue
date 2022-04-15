<template>
  <section id="songsWrapper">
    <screen-header>
      All Songs
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

    <song-list :items="state.songs" type="all-songs" ref="songList"/>
  </section>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { pluralize } from '@/utils'
import { songStore } from '@/stores'
import hasSongList from '@/mixins/has-song-list.ts'

export default mixins(hasSongList).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue')
  },

  filters: { pluralize },

  data: () => ({
    state: songStore.state
  })
})
</script>
