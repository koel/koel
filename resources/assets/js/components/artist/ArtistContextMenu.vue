<template>
  <ContextMenu ref="base" data-testid="artist-context-menu" extra-class="artist-menu">
    <template v-if="artist">
      <li @click="play">Play All</li>
      <li @click="shuffle">Shuffle All</li>
      <li class="separator" />
      <li @click="toggleFavorite">{{ artist.favorite ? 'Undo Favorite' : 'Favorite' }}</li>
      <li v-if="allowEdit" @click="requestEditForm">Edit…</li>
      <template v-if="isStandardArtist && allowDownload">
        <li class="separator" />
        <li @click="download">Download</li>
      </template>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { computed, ref, toRef } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { downloadService } from '@/services/downloadService'
import { useContextMenu } from '@/composables/useContextMenu'
import { useRouter } from '@/composables/useRouter'
import { eventBus } from '@/utils/eventBus'
import { playback } from '@/services/playbackManager'
import { usePolicies } from '@/composables/usePolicies'

const { go, url } = useRouter()
const { base, ContextMenu, open, trigger } = useContextMenu()
const { currentUserCan } = usePolicies()

const artist = ref<Artist>()
const allowDownload = toRef(commonStore.state, 'allows_download')
const allowEdit = ref(false)

const isStandardArtist = computed(() =>
  !artistStore.isUnknown(artist.value!)
  && !artistStore.isVarious(artist.value!),
)

const play = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value!))
  go(url('queue'))
})

const shuffle = () => trigger(async () => {
  playback().queueAndPlay(await playableStore.fetchSongsForArtist(artist.value!), true)
  go(url('queue'))
})

const download = () => trigger(() => downloadService.fromArtist(artist.value!))
const toggleFavorite = () => trigger(() => artistStore.toggleFavorite(artist.value!))
const requestEditForm = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_ARTIST_FORM', artist.value!))

eventBus.on('ARTIST_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _artist) => {
  artist.value = _artist
  await open(pageY, pageX)
  allowEdit.value = await currentUserCan.editArtist(_artist)
})
</script>
