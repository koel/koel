<template>
  <ArtistAlbumCard
    v-if="showing"
    :entity="artist"
    :layout="layout"
    :title="artist.name"
    @contextmenu="requestContextMenu"
    @dblclick="shuffle"
    @dragstart="onDragStart"
  >
    <template #name>
      <a :href="`#/artist/${artist.id}`" class="text-normal" data-testid="name">{{ artist.name }}</a>
    </template>
    <template #meta>
      <a
        :title="`Shuffle all songs by ${artist.name}`"
        class="shuffle-artist"
        data-testid="shuffle-artist"
        href
        role="button"
        @click.prevent="shuffle"
      >
        Shuffle
      </a>
      <a
        v-if="allowDownload"
        :title="`Download all songs by ${artist.name}`"
        class="download-artist"
        data-testid="download-artist"
        href
        role="button"
        @click.prevent="download"
      >
        Download
      </a>
    </template>
  </ArtistAlbumCard>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { eventBus, requireInjection } from '@/utils'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService, playbackService } from '@/services'
import { useDraggable } from '@/composables'
import { RouterKey } from '@/symbols'

import ArtistAlbumCard from '@/components/ui/ArtistAlbumCard.vue'

const router = requireInjection(RouterKey)

const { startDragging } = useDraggable('artist')

const props = withDefaults(defineProps<{ artist: Artist, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { artist, layout } = toRefs(props)

const allowDownload = toRef(commonStore.state, 'allow_download')

const showing = computed(() => artistStore.isStandard(artist.value))

const shuffle = async () => {
  playbackService.queueAndPlay(await songStore.fetchForArtist(artist.value), true /* shuffled */)
  router.go('queue')
}

const download = () => downloadService.fromArtist(artist.value)
const onDragStart = (event: DragEvent) => startDragging(event, artist.value)
const requestContextMenu = (event: MouseEvent) => eventBus.emit('ARTIST_CONTEXT_MENU_REQUESTED', event, artist.value)
</script>
