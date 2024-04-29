<template>
  <BaseCard
    v-if="showing"
    :entity="album"
    :layout="layout"
    :title="`${album.name} by ${album.artist_name}`"
    @contextmenu="requestContextMenu"
    @dblclick="shuffle"
    @dragstart="onDragStart"
  >
    <template #name>
      <a :href="`#/album/${album.id}`" class="font-medium" data-testid="name">{{ album.name }}</a>
      <a v-if="isStandardArtist" :href="`#/artist/${album.artist_id}`">{{ album.artist_name }}</a>
      <span v-else class="text-k-text-secondary">{{ album.artist_name }}</span>
    </template>

    <template #meta>
      <a :title="`Shuffle all songs in the album ${album.name}`" role="button" @click.prevent="shuffle">
        Shuffle
      </a>
      <a
        v-if="allowDownload"
        :title="`Download all songs in the album ${album.name}`"
        role="button"
        @click.prevent="download"
      >
        Download
      </a>
    </template>
  </BaseCard>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { eventBus } from '@/utils'
import { albumStore, artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useDraggable, useRouter } from '@/composables'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'

const { go } = useRouter()
const { startDragging } = useDraggable('album')

const props = withDefaults(defineProps<{ album: Album, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { album, layout } = toRefs(props)

const allowDownload = toRef(commonStore.state, 'allows_download')

const isStandardArtist = computed(() => artistStore.isStandard(album.value.artist_id))
const showing = computed(() => !albumStore.isUnknown(album.value))

const shuffle = async () => {
  playbackService.queueAndPlay(await songStore.fetchForAlbum(album.value), true /* shuffled */)
  go('queue')
}

const download = () => downloadService.fromAlbum(album.value)
const onDragStart = (event: DragEvent) => startDragging(event, album.value)
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ALBUM_CONTEXT_MENU_REQUESTED', event, album.value)
</script>
