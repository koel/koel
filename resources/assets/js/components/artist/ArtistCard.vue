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
      <a :href="url('artists.show', { id: artist.id })" class="font-medium" data-testid="name">
        <ExternalMark v-if="artist.is_external" class="mr-1" />
        {{ artist.name }}

        <FavoriteButton v-if="artist.favorite" :favorite="artist.favorite" class="ml-1" @toggle="toggleFavorite" />
      </a>
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
import { defineAsyncComponent } from '@/utils/helpers'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useDraggable } from '@/composables/useDragAndDrop'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'
import { useContextMenu } from '@/composables/useContextMenu'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'
import ExternalMark from '@/components/ui/ExternalMark.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'

const props = withDefaults(defineProps<{ artist: Artist, layout?: CardLayout }>(), { layout: 'full' })

const ContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))

const { go, url } = useRouter()
const { startDragging } = useDraggable('artist')
const { openContextMenu } = useContextMenu()

const { artist, layout } = toRefs(props)

// We're not checking for supports_batch_downloading here, as the number of songs by the artist is not yet known.
const allowDownload = toRef(commonStore.state, 'allows_download')

const showing = computed(() => artistStore.isStandard(artist.value))

const shuffle = async () => {
  playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value), true /* shuffled */)
  go(url('queue'))
}

const toggleFavorite = () => artistStore.toggleFavorite(artist.value)

const download = () => downloadService.fromArtist(artist.value)
const onDragStart = (event: DragEvent) => startDragging(event, artist.value)

const requestContextMenu = (event: MouseEvent) => openContextMenu<'ARTIST'>(ContextMenu, event, {
  artist: artist.value,
})
</script>
