<template>
  <section id="favoritesWrapper">
    <screen-header>
      Songs You Love
      <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="meta.songCount">
          {{ meta.songCount | pluralize('song') }}
          •
          {{ meta.totalLength }}
          <template v-if="sharedState.allowDownload && state.songs.length">
            •
            <a href @click.prevent="download" class="download" title="Download all songs in playlist" role="button">
              Download All
            </a>
          </template>
        </span>
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

    <song-list v-if="state.songs.length" :items="state.songs" type="favorites" ref="songList"/>

    <screen-placeholder v-else>
      <template v-slot:icon>
        <i class="fa fa-frown-o"></i>
      </template>
      No favorites yet.
      <span class="secondary d-block">
        Click the
        <i class="fa fa-heart-o"></i>
        icon to mark a song as favorite.
      </span>
    </screen-placeholder>
  </section>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { pluralize } from '@/utils'
import { favoriteStore, sharedStore } from '@/stores'
import { download } from '@/services'
import hasSongList from '@/mixins/has-song-list.ts'

export default mixins(hasSongList).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    ScreenPlaceholder: () => import('@/components/ui/screen-placeholder.vue')
  },

  filters: { pluralize },

  data: () => ({
    state: favoriteStore.state,
    sharedState: sharedStore.state
  }),

  methods: {
    download: (): void => download.fromFavorites()
  }
})
</script>
