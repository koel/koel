<template>
  <ContextMenuBase extra-class="artist-menu" ref="base" data-testid="artist-context-menu">
    <template v-if="artist">
      <li data-testid="play" @click="play">Play All</li>
      <li data-testid="shuffle" @click="shuffle">Shuffle All</li>
      <template v-if="isStandardArtist">
        <li class="separator"></li>
        <li data-testid="view-artist" @click="viewArtistDetails">Go to Artist</li>
      </template>
      <template v-if="isStandardArtist && allowDownload">
        <li class="separator"></li>
        <li data-testid="download" @click="download">Download</li>
      </template>
    </template>
  </ContextMenuBase>
</template>

<script lang="ts" setup>
import { computed, Ref, toRef } from 'vue'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useContextMenu } from '@/composables'
import router from '@/router'

const { context, base, ContextMenuBase, open, trigger } = useContextMenu()

const artist = toRef(context, 'artist') as Ref<Artist>
const allowDownload = toRef(commonStore.state, 'allow_download')

const isStandardArtist = computed(() =>
  !artistStore.isUnknown(artist.value)
  && !artistStore.isVarious(artist.value)
)

const play = () => trigger(async () => playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value)))

const shuffle = () => {
  trigger(async () => playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value), true))
}

const viewArtistDetails = () => trigger(() => router.go(`artist/${artist.value.id}`))
const download = () => trigger(() => downloadService.fromArtist(artist.value))

defineExpose({ open })
</script>
