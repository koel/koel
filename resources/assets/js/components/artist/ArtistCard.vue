<template>
  <BaseCard
    v-if="showing"
    :entity="artist"
    :layout="layout"
    :title="artist.name"
    @contextmenu="requestContextMenu"
    @dblclick="shuffle"
    @dragstart="onDragStart"
  >
    <template #name>
      <a :href="`#/artist/${artist.id}`" class="font-medium" data-testid="name">{{ artist.name }}</a>
    </template>
    <template #meta>
      <a :title="`Shuffle all songs by ${artist.name}`" role="button" @click.prevent="shuffle">
        Shuffle
      </a>
      <a v-if="allowDownload" :title="`Download all songs by ${artist.name}`" role="button" @click.prevent="download">
        Download
      </a>
    </template>
  </BaseCard>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { eventBus } from '@/utils'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useDraggable, useRouter } from '@/composables'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'

const { go } = useRouter()
const { startDragging } = useDraggable('artist')

const props = withDefaults(defineProps<{ artist: Artist, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { artist, layout } = toRefs(props)

// We're not checking for supports_batch_downloading here, as the number of songs by the artist is not yet known.
const allowDownload = toRef(commonStore.state, 'allows_download')

const showing = computed(() => artistStore.isStandard(artist.value))

const shuffle = async () => {
  playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value), true /* shuffled */)
  go('queue')
}

const download = () => downloadService.fromArtist(artist.value)
const onDragStart = (event: DragEvent) => startDragging(event, artist.value)
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', event, artist.value)
</script>
