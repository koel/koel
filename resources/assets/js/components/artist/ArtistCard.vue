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
      <LinkButton @click.prevent="shuffle"> Shuffle </LinkButton>
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
import { useContextMenu } from '@/composables/useContextMenu'
import { usePlayCollection } from '@/composables/usePlayCollection'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'
import CardThumbnail from '@/components/ui/album-artist/AlbumOrArtistCardThumbnail.vue'
import ExternalMark from '@/components/ui/ExternalMark.vue'
import MoreActionsButton from '@/components/ui/album-artist/AlbumOrArtistCardMoreActionsButton.vue'
import LinkButton from '@/components/ui/form/LinkButton.vue'

const props = defineProps<{ artist: Artist }>()

const ContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))

const { url } = useRouter()
const { startDragging } = useDraggable('artist')
const { openContextMenu } = useContextMenu()
const { playCollection } = usePlayCollection()

const { artist } = toRefs(props)

const showing = computed(() => artistStore.isStandard(artist.value))

const shuffle = () =>
  playCollection(() => playableStore.fetchSongsForArtist(artist.value), {
    shuffle: true,
    emptyMessage: 'The artist has no songs.',
  })

const toggleFavorite = () => artistStore.toggleFavorite(artist.value)

const onDragStart = (event: DragEvent) => startDragging(event, artist.value)

const requestContextMenu = (event: MouseEvent) =>
  openContextMenu<'ARTIST'>(ContextMenu, event, {
    artist: artist.value,
  })
</script>
