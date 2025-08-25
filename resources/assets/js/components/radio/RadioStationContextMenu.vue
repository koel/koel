<template>
  <ContextMenu ref="base" data-testid="radio-station-context-menu" extra-class="radio-station-menu">
    <template v-if="station">
      <li @click="togglePlayback">{{ station.playback_state === 'Playing' ? 'Stop' : 'Play' }}</li>
      <li class="separator" />
      <li @click="toggleFavorite">{{ station.favorite ? 'Undo Favorite' : 'Favorite' }}</li>
      <li class="separator" />
      <li v-if="allowEdit" @click="requestEditForm">Edit…</li>
      <li v-if="allowDelete" @click="maybeDelete">Delete</li>
    </template>
  </ContextMenu>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { eventBus } from '@/utils/eventBus'
import { radioStationStore } from '@/stores/radioStationStore'
import { playback } from '@/services/playbackManager'
import { usePolicies } from '@/composables/usePolicies'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'

const { base, ContextMenu, open, trigger } = useContextMenu()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { currentUserCan } = usePolicies()

const station = ref<RadioStation>()
const allowEdit = ref(false)
const allowDelete = ref(false)

const togglePlayback = () => trigger(async () => {
  const playbackService = playback('radio')

  if (station.value!.playback_state === 'Playing') {
    await playbackService.stop()
  } else {
    await playbackService.play(station.value!)
  }
})

const toggleFavorite = () => trigger(() => radioStationStore.toggleFavorite(station.value!))

const requestEditForm = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_RADIO_STATION_FORM', station.value!))

const maybeDelete = () => trigger(async () => {
  if (await showConfirmDialog('Delete the radio station? This action is NOT reversible!')) {
    await radioStationStore.delete(station.value!)
    toastSuccess(`Radio station deleted.`)
  }
})

eventBus.on('RADIO_STATION_CONTEXT_MENU_REQUESTED', async ({ pageX, pageY }, _station) => {
  station.value = _station
  await open(pageY, pageX)

  allowEdit.value = await currentUserCan.editRadioStation(station.value)
  allowDelete.value = await currentUserCan.deleteRadioStation(station.value)
})
</script>
