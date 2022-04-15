<template>
  <section id="recentlyPlayedWrapper">
    <screen-header>
      Recently Played
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

    <song-list v-if="state.songs.length" :items="state.songs" type="recently-played" :sortable="false"/>

    <screen-placeholder v-else>
      <template v-slot:icon>
        <i class="fa fa-clock-o"></i>
      </template>
      No songs recently played.
      <span class="secondary d-block">
        Start playing to populate this playlist.
      </span>
    </screen-placeholder>
  </section>
</template>

<script lang="ts">
import { eventBus, pluralize } from '@/utils'
import { recentlyPlayedStore } from '@/stores'
import hasSongList from '@/mixins/has-song-list.ts'
import mixins from 'vue-typed-mixins'

export default mixins(hasSongList).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    ScreenPlaceholder: () => import('@/components/ui/screen-placeholder.vue')
  },

  filters: { pluralize },

  data: () => ({
    state: recentlyPlayedStore.state
  }),

  methods: {
    getSongsToPlay (): Song[] {
      return this.state.songs
    }
  },

  created (): void {
    eventBus.on({
      'LOAD_MAIN_CONTENT': (view: MainViewName): void => {
        if (view === 'RecentlyPlayed') {
          recentlyPlayedStore.fetchAll()
        }
      }
    })
  }
})
</script>

<style lang="scss">
#recentlyPlayedWrapper {
  .none {
    padding: 16px 24px;
  }
}
</style>
