<template>
  <BaseCard
    v-if="showing"
    :entity="album"
    :layout="layout"
    :title="`${album.name} by ${album.artist_name}`"
    class="group"
    @contextmenu="requestContextMenu"
    @dblclick="shuffle"
    @dragstart="onDragStart"
  >
    <template #name>
      <div class="flex gap-2 items-center">
        <a :href="url('albums.show', { id: album.id })" class="font-medium flex-1" data-testid="name">
          <ExternalMark v-if="album.is_external" class="mr-1" />
          {{ album.name }}

          <FavoriteButton v-if="album.favorite" :favorite="album.favorite" class="ml-1" @toggle="toggleFavorite" />
        </a>

        <span
          v-if="showReleaseYear && album.year"
          :title="`Released in ${album.year}`"
          class="text-sm text-k-text-primary rounded px-2 py-[2px] bg-white/10"
        >
          {{ album.year }}
        </span>
      </div>

      <div class="space-x-2">
        <a v-if="isStandardArtist" :href="url('artists.show', { id: album.artist_id })">{{ album.artist_name }}</a>
        <span v-else class="text-k-text-secondary">{{ album.artist_name }}</span>
      </div>
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
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'
import { useContextMenu } from '@/composables/useContextMenu'
import { defineAsyncComponent } from '@/utils/helpers'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'
import ExternalMark from '@/components/ui/ExternalMark.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'

const props = withDefaults(defineProps<{
  album: Album
  layout?: CardLayout
  showReleaseYear?: boolean
}>(), {
  layout: 'full',
  showReleaseYear: false,
})

const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))

const { go, url } = useRouter()
const { startDragging } = useDraggable('album')
const { openContextMenu } = useContextMenu()

const { album, layout, showReleaseYear } = toRefs(props)

// We're not checking for supports_batch_downloading here, as the number of songs on the album is not yet known.
const allowDownload = toRef(commonStore.state, 'allows_download')

const isStandardArtist = computed(() => artistStore.isStandard(album.value.artist_id))
const showing = computed(() => !albumStore.isUnknown(album.value))

const shuffle = async () => {
  go(url('queue'))
  await playback().queueAndPlay(await playableStore.fetchSongsForAlbum(album.value), true /* shuffled */)
}

const toggleFavorite = () => albumStore.toggleFavorite(album.value)

const download = () => downloadService.fromAlbum(album.value)
const onDragStart = (event: DragEvent) => startDragging(event, album.value)

const requestContextMenu = (event: MouseEvent) => openContextMenu<'ALBUM'>(AlbumContextMenu, event, {
  album: album.value,
})
</script>
