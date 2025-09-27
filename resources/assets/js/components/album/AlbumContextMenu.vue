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
    <Separator />
    <MenuItem @click="showEmbedModal">Embed…</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef, toRefs } from 'vue'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useContextMenu } from '@/composables/useContextMenu'
import { usePolicies } from '@/composables/usePolicies'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { playback } from '@/services/playbackManager'

const props = defineProps<{ album: Album }>()
const { album } = toRefs(props)

const { go, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { currentUserCan } = usePolicies()

const allowDownload = toRef(commonStore.state, 'allows_download')
const allowEdit = ref(false)

const isStandardAlbum = computed(() => !albumStore.isUnknown(album.value))

const play = () => trigger(async () => {
  go(url('queue'))
  await playback().queueAndPlay(await playableStore.fetchSongsForAlbum(album.value))
})

const shuffle = () => trigger(async () => {
  go(url('queue'))
  await playback().queueAndPlay(await playableStore.fetchSongsForAlbum(album.value), true)
})

const edit = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_ALBUM_FORM', album.value))
const toggleFavorite = () => trigger(() => albumStore.toggleFavorite(album.value))
const download = () => trigger(() => downloadService.fromAlbum(album.value))
const showEmbedModal = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_EMBED_FORM', album.value))

onMounted(async () => {
  allowEdit.value = await currentUserCan.editAlbum(album.value)
})
</script>
