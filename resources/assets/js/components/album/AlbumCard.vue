<template>
  <BaseCard
    v-if="showing"
    :entity="album"
    class="group"
    @contextmenu="requestContextMenu"
    @dblclick="shuffle"
    @dragstart="onDragStart"
  >
    <template #thumbnail>
      <CardThumbnail :entity="album" @toggle-favorite="toggleFavorite" />
    </template>

    <template #name>
      <div class="flex gap-2 items-center">
        <a
          :href="url('albums.show', { id: album.id })"
          :title="album.name"
          class="font-medium flex-1"
          data-testid="name"
        >
          <ExternalMark v-if="album.is_external" class="mr-1" />
          {{ album.name }}
        </a>

        <span v-if="showReleaseYear && album.year" class="text-sm text-k-fg rounded-sm px-2 py-[2px] bg-k-fg-10">
          {{ album.year }}
        </span>
      </div>

      <div class="space-x-2">
        <a v-if="isStandardArtist" :href="url('artists.show', { id: album.artist_id })">{{ album.artist_name }}</a>
        <span v-else>{{ album.artist_name }}</span>
      </div>
    </template>

    <template #meta>
      <LinkButton @click.prevent="shuffle"> Shuffle </LinkButton>
      <MoreActionsButton @click="requestContextMenu" />
    </template>
  </BaseCard>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { playableStore } from '@/stores/playableStore'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'
import { useContextMenu } from '@/composables/useContextMenu'
import { usePlayCollection } from '@/composables/usePlayCollection'
import { defineAsyncComponent } from '@/utils/helpers'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'
import CardThumbnail from '@/components/ui/album-artist/AlbumOrArtistCardThumbnail.vue'
import ExternalMark from '@/components/ui/ExternalMark.vue'
import MoreActionsButton from '@/components/ui/album-artist/AlbumOrArtistCardMoreActionsButton.vue'
import LinkButton from '@/components/ui/form/LinkButton.vue'

const props = withDefaults(
  defineProps<{
    album: Album
    showReleaseYear?: boolean
  }>(),
  {
    showReleaseYear: false,
  },
)

const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))

const { url } = useRouter()
const { startDragging } = useDraggable('album')
const { openContextMenu } = useContextMenu()
const { playCollection } = usePlayCollection()

const { album, showReleaseYear } = toRefs(props)

const isStandardArtist = computed(() => artistStore.isStandard(album.value.artist_name))
const showing = computed(() => !albumStore.isUnknown(album.value))

const shuffle = () =>
  playCollection(() => playableStore.fetchSongsForAlbum(album.value), {
    shuffle: true,
    emptyMessage: 'The album is empty.',
  })

const toggleFavorite = () => albumStore.toggleFavorite(album.value)

const onDragStart = (event: DragEvent) => startDragging(event, album.value)

const requestContextMenu = (event: MouseEvent) =>
  openContextMenu<'ALBUM'>(AlbumContextMenu, event, {
    album: album.value,
  })
</script>
