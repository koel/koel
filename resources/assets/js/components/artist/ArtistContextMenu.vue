<template>
  <ContextMenu ref="base" data-testid="artist-context-menu" extra-class="artist-menu">
    <template v-if="artist">
      <li @click="play">Play All</li>
      <li @click="shuffle">Shuffle All</li>
      <li class="separator" />
      <li @click="toggleFavorite">{{ artist.favorite ? 'Undo Favorite' : 'Favorite' }}</li>
      <template v-if="isStandardArtist">
        <li class="separator" />
        <li @click="viewArtistDetails">Go to Artist</li>
      </template>
      <template v-if="isStandardArtist && allowDownload">
        <li class="separator" />
        <li @click="download">Download</li>
      </template>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { playback } from '@/services/playbackManager'

const { go, url } = useRouter()
const { base, ContextMenu, open, trigger } = useContextMenu()

const artist = ref<Artist>()
const allowDownload = toRef(commonStore.state, 'allows_download')

const isStandardArtist = computed(() =>
  !artistStore.isUnknown(artist.value!)
  && !artistStore.isVarious(artist.value!),
)

const play = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value!))
  go(url('queue'))
})

const shuffle = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value!), true)
  go(url('queue'))
})

const viewArtistDetails = () => trigger(() => go(url('artists.show', { id: artist.value!.id })))
const download = () => trigger(() => downloadService.fromArtist(artist.value!))
const toggleFavorite = () => trigger(() => artistStore.toggleFavorite(artist.value!))

eventBus.on('ARTIST_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _artist) => {
  artist.value = _artist
  await open(pageY, pageX)
})
</script>
