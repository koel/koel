<template>
  <ul>
    <MenuItem @click="play">Play All</MenuItem>
    <MenuItem @click="shuffle">Shuffle All</MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">{{ album.favorite ? 'Undo Favorite' : 'Favorite' }}</MenuItem>
    <template v-if="allowEdit">
      <MenuItem @click="edit">Edit…</MenuItem>
    </template>
    <Separator />
    <template v-if="isStandardAlbum && allowDownload">
      <MenuItem @click="download">Download</MenuItem>
    </template>
    <template v-if="canToggleOffline">
      <Separator />
      <MenuItem @click="toggleOffline">{{ allCached ? 'Remove Offline Versions' : 'Make Available Offline' }}</MenuItem>
    </template>
    <Separator />
    <MenuItem @click="showEmbedModal">Embed…</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef, toRefs } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { useDownload } from '@/composables/useDownload'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { pluralize } from '@/utils/formatters'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { usePolicies } from '@/composables/usePolicies'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'

const props = defineProps<{ album: Album }>()
const { album } = toRefs(props)

const EditAlbumForm = defineAsyncComponent(() => import('@/components/album/EditAlbumForm.vue'))
const CreateEmbedForm = defineAsyncComponent(() => import('@/components/embed/CreateEmbedForm.vue'))

const { go, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { currentUserCan } = usePolicies()

const allowDownload = toRef(commonStore.state, 'allows_download')
const allowEdit = ref(false)

const isStandardAlbum = computed(() => !albumStore.isUnknown(album.value))

const play = () =>
  trigger(async () => {
    go(url('queue'))
    await playback().queueAndPlay(await playableStore.fetchSongsForAlbum(album.value))
  })

const shuffle = () =>
  trigger(async () => {
    go(url('queue'))
    await playback().queueAndPlay(await playableStore.fetchSongsForAlbum(album.value), true)
  })

const edit = () => trigger(() => openModal<'EDIT_ALBUM_FORM'>(EditAlbumForm, { album: album.value }))
const toggleFavorite = () => trigger(() => albumStore.toggleFavorite(album.value))
const { fromAlbum } = useDownload()
const download = () => trigger(() => fromAlbum(album.value))
const showEmbedModal = () => trigger(() => openModal<'CREATE_EMBED_FORM'>(CreateEmbedForm, { embeddable: album.value }))

const { swReady, makePlayablesAvailableOffline, removePlayablesOfflineCache, allPlayablesCached } = useOfflinePlayback()
const canToggleOffline = computed(() => swReady.value)
const albumSongs = ref<Playable[]>([])
const allCached = computed(() => allPlayablesCached(albumSongs.value))

const toggleOffline = () =>
  trigger(async () => {
    const { toastSuccess } = useMessageToaster()
    if (!albumSongs.value.length) return

    if (allCached.value) {
      removePlayablesOfflineCache(albumSongs.value)
      toastSuccess(`Removed offline versions for "${album.value.name}".`)
    } else {
      makePlayablesAvailableOffline(albumSongs.value)
      toastSuccess(`Making ${pluralize(albumSongs.value, 'song')} available offline…`)
    }
  })

onMounted(async () => {
  allowEdit.value = await currentUserCan.editAlbum(album.value)
  albumSongs.value = await playableStore.fetchSongsForAlbum(album.value)
})
</script>
