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
      <a :href="url('artists.show', { id: artist.id })" class="font-medium" data-testid="name">{{ artist.name }}</a>
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
import { eventBus } from '@/utils/eventBus'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { songStore } from '@/stores/songStore'
import { downloadService } from '@/services/downloadService'
import { playbackService } from '@/services/playbackService'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'

const props = withDefaults(defineProps<{ artist: Artist, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { go, url } = useRouter()
const { startDragging } = useDraggable('artist')

const { artist, layout } = toRefs(props)

// We're not checking for supports_batch_downloading here, as the number of songs by the artist is not yet known.
const allowDownload = toRef(commonStore.state, 'allows_download')

const showing = computed(() => artistStore.isStandard(artist.value))

const shuffle = async () => {
  playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value), true /* shuffled */)
  go(url('queue'))
}

const download = () => downloadService.fromArtist(artist.value)
const onDragStart = (event: DragEvent) => startDragging(event, artist.value)
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', event, artist.value)
</script>
