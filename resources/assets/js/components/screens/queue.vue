<template>
  <section id="queueWrapper">
    <screen-header>
      Current Queue
      <controls-toggler :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="meta.songCount" data-test="list-meta">
          {{ meta.songCount | pluralize('song') }} • {{ meta.totalLength }}
        </span>
      </template>

      <template v-slot:controls>
        <song-list-controls
          v-if="state.songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
          @clearQueue="clearQueue"
          :songs="state.songs"
          :config="songListControlConfig"
          :selectedSongs="selectedSongs"
        />
      </template>
    </screen-header>

    <song-list
      v-if="state.songs.length"
      :items="state.songs"
      :config="{ sortable: false }"
      type="queue"
      ref="songList"
    />

    <screen-placeholder v-else>
      <template v-slot:icon>
        <i class="fa fa-coffee"></i>
      </template>

      No songs queued.
      <span class="secondary d-block" v-if="shouldShowShufflingAllLink">
        How about
        <a class="start" @click.prevent="shuffleAll">shuffling all songs</a>?
      </span>
    </screen-placeholder>
  </section>
</template>

<script lang="ts">
import mixins from 'vue-typed-mixins'
import { pluralize } from '@/utils'
import { queueStore, songStore } from '@/stores'
import { playback } from '@/services'
import hasSongList from '@/mixins/has-song-list.ts'

export default mixins(hasSongList).extend({
  components: {
    ScreenHeader: () => import('@/components/ui/screen-header.vue'),
    ScreenPlaceholder: () => import('@/components/ui/screen-placeholder.vue')
  },

  filters: { pluralize },

  data: () => ({
    state: queueStore.state,
    songState: songStore.state,
    songListControlConfig: {
      clearQueue: true
    }
  }),

  computed: {
    shouldShowShufflingAllLink (): boolean {
      return this.songState.songs.length > 0
    }
  },

  methods: {
    getSongsToPlay (): Song[] {
      return this.state.songs.length ? (this.$refs.songList as any).getAllSongsWithSort() : songStore.all
    },

    shuffleAll: async () => await playback.queueAndPlay(songStore.all, true),
    clearQueue: (): void => queueStore.clear()
  }
})
</script>
