<template>
  <BaseCard
    v-if="showing"
    :entity="artist"
    @contextmenu="requestContextMenu"
    @dblclick="shuffle"
    @dragstart="onDragStart"
  >
    <template #thumbnail>
      <CardThumbnail :entity="artist" @toggle-favorite="toggleFavorite" />
    </template>

    <template #name>
      <a :href="url('artists.show', { id: artist.id })" :title="artist.name" class="font-medium" data-testid="name">
        <ExternalMark v-if="artist.is_external" class="mr-1" />
        {{ artist.name }}
      </a>
    </template>
    <template #meta>
      <a role="button" @click.prevent="shuffle"> Shuffle </a>
      <MoreActionsButton @click="requestContextMenu" />
    </template>
  </BaseCard>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { defineAsyncComponent } from '@/utils/helpers'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'
import { useContextMenu } from '@/composables/useContextMenu'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'
import CardThumbnail from '@/components/ui/album-artist/AlbumOrArtistCardThumbnail.vue'
import ExternalMark from '@/components/ui/ExternalMark.vue'
import MoreActionsButton from '@/components/ui/album-artist/AlbumOrArtistCardMoreActionsButton.vue'

const props = defineProps<{ artist: Artist }>()

const ContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))

const { go, url } = useRouter()
const { startDragging } = useDraggable('artist')
const { openContextMenu } = useContextMenu()

const { artist } = toRefs(props)

const showing = computed(() => artistStore.isStandard(artist.value))

const shuffle = async () => {
  playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value), true /* shuffled */)
  go(url('queue'))
}

const toggleFavorite = () => artistStore.toggleFavorite(artist.value)

const onDragStart = (event: DragEvent) => startDragging(event, artist.value)

const requestContextMenu = (event: MouseEvent) =>
  openContextMenu<'ARTIST'>(ContextMenu, event, {
    artist: artist.value,
  })
</script>
