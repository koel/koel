<template>
  <section id="recentlyPlayedWrapper">
    <ScreenHeader>
      Recently Played
      <ControlsToggle :showing-controls="showingControls" @toggleControls="toggleControls"/>

      <template v-slot:meta>
        <span v-if="songs.length">{{ pluralize(songs.length, 'song') }} • {{ duration }}</span>
      </template>

      <template v-slot:controls>
        <SongListControls
          v-if="songs.length && (!isPhone || showingControls)"
          @playAll="playAll"
          @playSelected="playSelected"
        />
      </template>
    </ScreenHeader>

    <SongList v-if="songs.length" ref="songList" @press:enter="onPressEnter"/>

    <ScreenEmptyState v-else>
      <template v-slot:icon>
        <i class="fa fa-clock-o"></i>
      </template>
      No songs recently played.
      <span class="secondary d-block">Start playing to populate this playlist.</span>
    </ScreenEmptyState>
  </section>
</template>

<script lang="ts" setup>
import { eventBus, pluralize } from '@/utils'
import { recentlyPlayedStore } from '@/stores'
import { useSongList } from '@/composables'
import { toRef } from 'vue'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'

const {
  SongList,
  SongListControls,
  ControlsToggle,
  songs,
  songList,
  duration,
  showingControls,
  isPhone,
  onPressEnter,
  playAll,
  playSelected,
  toggleControls
} = useSongList(toRef(recentlyPlayedStore.state, 'songs'), 'recently-played', { sortable: false })

let initialized = false

eventBus.on({
  'LOAD_MAIN_CONTENT': async (view: MainViewName) => {
    if (view === 'RecentlyPlayed' && !initialized) {
      await recentlyPlayedStore.fetch()
      initialized = true
    }
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
