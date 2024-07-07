<template>
  <ContextMenu ref="base" data-testid="artist-context-menu" extra-class="artist-menu">
    <template v-if="artist">
      <li @click="play">Play All</li>
      <li @click="shuffle">Shuffle All</li>
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
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useContextMenu, useRouter } from '@/composables'
import { eventBus } from '@/utils'

const { go } = useRouter()
const { base, ContextMenu, open, trigger } = useContextMenu()

const artist = ref<Artist>()
const allowDownload = toRef(commonStore.state, 'allows_download')

const isStandardArtist = computed(() =>
  !artistStore.isUnknown(artist.value!)
  && !artistStore.isVarious(artist.value!)
)

const play = () => trigger(async () => {
  playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value!))
  go('queue')
})

const shuffle = () => trigger(async () => {
  playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value!), true)
  go('queue')
})

const viewArtistDetails = () => trigger(() => go(`artist/${artist.value!.id}`))
const download = () => trigger(() => downloadService.fromArtist(artist.value!))

eventBus.on('ARTIST_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _artist) => {
  artist.value = _artist
  await open(pageY, pageX)
})
</script>
