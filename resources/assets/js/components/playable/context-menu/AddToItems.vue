<template>
  <MenuItem>
    Add To
    <template #subMenuItems>
      <template v-if="queue.length">
        <MenuItem v-if="currentSong" @click="queueAfterCurrent">After Current</MenuItem>
        <MenuItem @click="queueToBottom">Bottom of Queue</MenuItem>
        <MenuItem @click="queueToTop">Top of Queue</MenuItem>
      </template>
      <MenuItem v-else @click="queueToBottom">Queue</MenuItem>
      <template v-if="!isCurrentScreen('Favorites') && !(playables.length === 1 && playables[0].favorite)">
        <Separator />
        <MenuItem @click="addToFavorites">Favorites</MenuItem>
      </template>
      <Separator v-if="normalPlaylists.length" />
      <template class="block">
        <ul v-if="normalPlaylists.length" class="scroll-mask-y relative max-h-48 overflow-y-auto">
          <MenuItem v-for="playlist in normalPlaylists" :key="playlist.id" @click="addToExistingPlaylist(playlist)">
            {{ playlist.name }}
          </MenuItem>
        </ul>
      </template>
      <Separator />
      <MenuItem @click="addToNewPlaylist">New Playlist…</MenuItem>
    </template>
  </MenuItem>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { playlistStore } from '@/stores/playlistStore'
import { queueStore } from '@/stores/queueStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { usePlayableMenuMethods } from '@/composables/usePlayableMenuMethods'
import { useRouter } from '@/composables/useRouter'

const props = defineProps<{ playables: Playable[] }>()
const { playables } = toRefs(props)

const { MenuItem, Separator, closeContextMenu } = useContextMenu()
const { isCurrentScreen } = useRouter()

const { queueAfterCurrent, queueToBottom, queueToTop, addToFavorites, addToExistingPlaylist, addToNewPlaylist } =
  usePlayableMenuMethods(playables, closeContextMenu)

const queue = toRef(queueStore.state, 'playables')
const currentSong = computed(() => queueStore.current)
const normalPlaylists = computed(() => playlistStore.state.playlists.filter(({ is_smart }) => !is_smart))
</script>
