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
import { useDownload } from '@/composables/useDownload'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { useRouter } from '@/composables/useRouter'
import { playback } from '@/services/playbackManager'
import { usePolicies } from '@/composables/usePolicies'

const props = defineProps<{ artist: Artist }>()
const { artist } = toRefs(props)

const EditArtistForm = defineAsyncComponent(() => import('@/components/artist/EditArtistForm.vue'))
const CreateEmbedForm = defineAsyncComponent(() => import('@/components/embed/CreateEmbedForm.vue'))

const { go, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { currentUserCan } = usePolicies()

const allowDownload = toRef(commonStore.state, 'allows_download')
const allowEdit = ref(false)

const isStandardArtist = computed(() => !artistStore.isUnknown(artist.value) && !artistStore.isVarious(artist.value))

const play = () =>
  trigger(async () => {
    go(url('queue'))
    await playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value))
  })

const shuffle = () =>
  trigger(async () => {
    go(url('queue'))
    await playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value), true)
  })

const { fromArtist } = useDownload()
const download = () => trigger(() => fromArtist(artist.value))
const toggleFavorite = () => trigger(() => artistStore.toggleFavorite(artist.value))
const requestEditForm = () => trigger(() => openModal<'EDIT_ARTIST_FORM'>(EditArtistForm, { artist: artist.value }))
const showEmbedModal = () =>
  trigger(() => openModal<'CREATE_EMBED_FORM'>(CreateEmbedForm, { embeddable: artist.value }))

onMounted(async () => {
  allowEdit.value = await currentUserCan.editArtist(artist.value)
})
</script>
