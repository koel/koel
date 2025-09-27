<template>
  <ul>
    <MenuItem @click="play">Play All</MenuItem>
    <MenuItem @click="shuffle">Shuffle All</MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">{{ artist.favorite ? 'Undo Favorite' : 'Favorite' }}</MenuItem>
    <MenuItem v-if="allowEdit" @click="requestEditForm">Edit…</MenuItem>
    <template v-if="isStandardArtist && allowDownload">
      <Separator />
      <MenuItem @click="download">Download</MenuItem>
    </template>
    <Separator />
    <MenuItem @click="showEmbedModal">Embed…</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref, toRef, toRefs } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { playback } from '@/services/playbackManager'
import { usePolicies } from '@/composables/usePolicies'

const props = defineProps<{ artist: Artist }>()
const { artist } = toRefs(props)

const { go, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { currentUserCan } = usePolicies()

const allowDownload = toRef(commonStore.state, 'allows_download')
const allowEdit = ref(false)

const isStandardArtist = computed(() =>
  !artistStore.isUnknown(artist.value)
  && !artistStore.isVarious(artist.value),
)

const play = () => trigger(async () => {
  go(url('queue'))
  await playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value))
})

const shuffle = () => trigger(async () => {
  go(url('queue'))
  await playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value), true)
})

const download = () => trigger(() => downloadService.fromArtist(artist.value))
const toggleFavorite = () => trigger(() => artistStore.toggleFavorite(artist.value))
const requestEditForm = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_ARTIST_FORM', artist.value))
const showEmbedModal = () => trigger(() => eventBus.emit('MODAL_SHOW_CREATE_EMBED_FORM', artist.value))

onMounted(async () => {
  allowEdit.value = await currentUserCan.editArtist(artist.value)
})
</script>
