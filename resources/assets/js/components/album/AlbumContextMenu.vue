<template>
  <ul>
    <MenuItem @click="play">Play All</MenuItem>
    <MenuItem @click="shuffle">Shuffle All</MenuItem>
    <Separator />
    <MenuItem v-if="!isOnAlbumScreen" @click="viewDetails">View Details</MenuItem>
    <MenuItem v-if="hasStandardArtist">
      Go to
      <template #subMenuItems>
        <MenuItem :title="album.artist_name" @click="viewArtist">
          <template #icon>
            <MicVocalIcon :size="16" class="inline-block" />
          </template>
          {{ album.artist_name }}
        </MenuItem>
      </template>
    </MenuItem>
    <Separator v-if="!isOnAlbumScreen || hasStandardArtist" />
    <MenuItem @click="toggleFavorite">{{ album.favorite ? 'Undo Favorite' : 'Favorite' }}</MenuItem>
    <ContextMenuRatingItem :rateable="album" />
    <template v-if="allowEdit">
      <MenuItem @click="edit">Edit…</MenuItem>
    </template>
    <Separator />
    <template v-if="isStandardAlbum && allowDownload">
      <MenuItem @click="download">Download</MenuItem>
    </template>
    <ContextMenuOfflineItem :playables="albumSongs" :collection-name="album.name" />
    <template v-if="allowEmbedding">
      <Separator />
      <MenuItem @click="showEmbedModal">Embed…</MenuItem>
    </template>
    <ContextMenuMusicBrainzItem entity-type="release" :mbid="album.mbid" />
  </ul>
</template>

<script lang="ts" setup>
import { MicVocalIcon } from 'lucide-vue-next'
import { computed, onMounted, ref, toRef, toRefs } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { useDownload } from '@/composables/useDownload'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { usePolicies } from '@/composables/usePolicies'
import { useRouter } from '@/composables/useRouter'
import { usePlayCollection } from '@/composables/usePlayCollection'

import ContextMenuRatingItem from '@/components/ui/context-menu/ContextMenuRatingItem.vue'
import ContextMenuMusicBrainzItem from '@/components/ui/context-menu/ContextMenuMusicBrainzItem.vue'
import ContextMenuOfflineItem from '@/components/ui/context-menu/ContextMenuOfflineItem.vue'

const props = defineProps<{ album: Album }>()
const { album } = toRefs(props)

const EditAlbumForm = defineAsyncComponent(() => import('@/components/album/EditAlbumForm.vue'))
const CreateEmbedForm = defineAsyncComponent(() => import('@/components/embed/CreateEmbedForm.vue'))

const { getRouteParam, go, isCurrentScreen, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { currentUserCan } = usePolicies()

const allowDownload = toRef(commonStore.state, 'allows_download')
const allowEmbedding = computed(() => commonStore.state.allows_embedding && currentUserCan.createEmbeds())
const allowEdit = computed(() => currentUserCan.editAlbum(album.value))

const isStandardAlbum = computed(() => !albumStore.isUnknown(album.value))
const hasStandardArtist = computed(() => artistStore.isStandard(album.value.artist_name))
const isOnAlbumScreen = computed(() => isCurrentScreen('Album') && getRouteParam('id') === album.value.id)

const viewDetails = () => trigger(() => go(url('albums.show', { id: album.value.id })))
const viewArtist = () => trigger(() => go(url('artists.show', { id: album.value.artist_id })))

const { playCollection } = usePlayCollection()

const playSongs = (shuffle: boolean) =>
  trigger(() =>
    playCollection(() => playableStore.fetchSongsForAlbum(album.value), {
      shuffle,
      emptyMessage: 'The album is empty.',
    }),
  )

const play = () => playSongs(false)
const shuffle = () => playSongs(true)

const edit = () => trigger(() => openModal<'EDIT_ALBUM_FORM'>(EditAlbumForm, { album: album.value }))
const toggleFavorite = () => trigger(() => albumStore.toggleFavorite(album.value))
const { fromAlbum } = useDownload()
const download = () => trigger(() => fromAlbum(album.value))
const showEmbedModal = () => trigger(() => openModal<'CREATE_EMBED_FORM'>(CreateEmbedForm, { embeddable: album.value }))

const albumSongs = ref<Playable[]>([])

onMounted(async () => {
  albumSongs.value = await playableStore.fetchSongsForAlbum(album.value)
})
</script>
