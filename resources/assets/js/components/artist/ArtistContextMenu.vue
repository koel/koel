<template>
  <ul>
    <MenuItem @click="play">Play All</MenuItem>
    <MenuItem @click="shuffle">Shuffle All</MenuItem>
    <Separator />
    <template v-if="!isOnArtistScreen">
      <MenuItem @click="viewDetails">View Details</MenuItem>
      <Separator />
    </template>
    <MenuItem @click="toggleFavorite">{{ artist.favorite ? 'Undo Favorite' : 'Favorite' }}</MenuItem>
    <ContextMenuRatingItem :rateable="artist" />
    <MenuItem v-if="allowEdit" @click="requestEditForm">Edit…</MenuItem>
    <template v-if="isStandardArtist && allowDownload">
      <Separator />
      <MenuItem @click="download">Download</MenuItem>
    </template>
    <template v-if="allowEmbedding">
      <Separator />
      <MenuItem @click="showEmbedModal">Embed…</MenuItem>
    </template>
    <ContextMenuMusicBrainzItem entity-type="artist" :mbid="artist.mbid" />
  </ul>
</template>

<script lang="ts" setup>
import { computed, toRef, toRefs } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { useDownload } from '@/composables/useDownload'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useModal } from '@/composables/useModal'
import { useRouter } from '@/composables/useRouter'
import { usePolicies } from '@/composables/usePolicies'
import { usePlayCollection } from '@/composables/usePlayCollection'

import ContextMenuRatingItem from '@/components/ui/context-menu/ContextMenuRatingItem.vue'
import ContextMenuMusicBrainzItem from '@/components/ui/context-menu/ContextMenuMusicBrainzItem.vue'

const props = defineProps<{ artist: Artist }>()
const { artist } = toRefs(props)

const EditArtistForm = defineAsyncComponent(() => import('@/components/artist/EditArtistForm.vue'))
const CreateEmbedForm = defineAsyncComponent(() => import('@/components/embed/CreateEmbedForm.vue'))

const { getRouteParam, go, isCurrentScreen, url } = useRouter()
const { MenuItem, Separator, trigger } = useContextMenu()
const { openModal } = useModal()
const { currentUserCan } = usePolicies()

const isOnArtistScreen = computed(() => isCurrentScreen('Artist') && getRouteParam('id') === artist.value.id)
const viewDetails = () => trigger(() => go(url('artists.show', { id: artist.value.id })))

const allowDownload = toRef(commonStore.state, 'allows_download')
const allowEmbedding = computed(() => commonStore.state.allows_embedding && currentUserCan.createEmbeds())
const allowEdit = computed(() => currentUserCan.editArtist(artist.value))

const isStandardArtist = computed(() => !artistStore.isUnknown(artist.value) && !artistStore.isVarious(artist.value))

const { playCollection } = usePlayCollection()

const playSongs = (shuffle: boolean) =>
  trigger(() =>
    playCollection(() => playableStore.fetchSongsForArtist(artist.value), {
      shuffle,
      emptyMessage: 'The artist has no songs.',
    }),
  )

const play = () => playSongs(false)
const shuffle = () => playSongs(true)

const { fromArtist } = useDownload()
const download = () => trigger(() => fromArtist(artist.value))
const toggleFavorite = () => trigger(() => artistStore.toggleFavorite(artist.value))
const requestEditForm = () => trigger(() => openModal<'EDIT_ARTIST_FORM'>(EditArtistForm, { artist: artist.value }))
const showEmbedModal = () =>
  trigger(() => openModal<'CREATE_EMBED_FORM'>(CreateEmbedForm, { embeddable: artist.value }))
</script>
