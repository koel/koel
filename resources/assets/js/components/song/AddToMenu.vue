<template>
  <div class="add-to w-full max-w-[256px] min-w-[200px] p-3 space-y-3" data-testid="add-to-menu" tabindex="0">
    <section class="existing-playlists">
      <p class="mb-2 text-[0.9rem]">Add {{ pluralize(playables, 'item') }} to</p>

      <ul v-koel-overflow-fade class="relative max-h-48 overflow-y-scroll space-y-1.5">
        <template v-if="config.queue">
          <template v-if="queue.length">
            <li
              v-if="currentPlayable"
              class="queue-after-current"
              data-testid="queue-after-current"
              tabindex="0"
              @click="queueAfterCurrent"
            >
              After Current
            </li>
            <li class="bottom-queue" data-testid="queue-bottom" tabindex="0" @click="queueToBottom">
              Bottom of Queue
            </li>
            <li class="top-queue" data-testid="queue-top" tabindex="0" @click="queueToTop">Top of Queue</li>
          </template>
          <li v-else data-testid="queue" tabindex="0" @click="queueToBottom">Queue</li>
        </template>

        <li
          v-if="config.favorites"
          class="favorites"
          data-testid="add-to-favorites"
          tabindex="0"
          @click="addToFavorites"
        >
          Favorites
        </li>

        <li
          v-for="playlist in playlists"
          :key="playlist.id"
          class="playlist"
          data-testid="add-to-playlist"
          tabindex="0"
          @click="addToExistingPlaylist(playlist)"
        >
          {{ playlist.name }}
        </li>
      </ul>
    </section>

    <Btn
      class="!w-full !border !border-solid !border-white/20"
      transparent
      @click.prevent="addToNewPlaylist"
    >
      New Playlist…
    </Btn>
  </div>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs, watch } from 'vue'
import { pluralize } from '@/utils'
import { playlistStore, queueStore } from '@/stores'
import { usePlayableMenuMethods } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'

const props = defineProps<{ playables: Playable[], config: AddToMenuConfig }>()
const { playables, config } = toRefs(props)

const queue = toRef(queueStore.state, 'playables')
const currentPlayable = queueStore.current

const allPlaylists = toRef(playlistStore.state, 'playlists')
const playlists = computed(() => allPlaylists.value.filter(({ is_smart }) => !is_smart))

const emit = defineEmits<{ (e: 'closing'): void }>()
const close = () => emit('closing')

const {
  queueAfterCurrent,
  queueToBottom,
  queueToTop,
  addToFavorites,
  addToExistingPlaylist,
  addToNewPlaylist
} = usePlayableMenuMethods(playables, close)

watch(playables, () => playables.value.length || close())
</script>

<style lang="postcss" scoped>
li {
  @apply h-9 leading-9 py-0 px-3 whitespace-nowrap overflow-hidden text-ellipsis rounded bg-white/5 cursor-pointer
  hover:bg-k-highlight hover:text-k-text-primary;
}
</style>
