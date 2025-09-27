<template>
  <ul>
    <MenuItem @click="togglePlayback">
      {{ station.playback_state === 'Playing' ? 'Stop' : 'Play' }}
    </MenuItem>
    <Separator />
    <MenuItem @click="toggleFavorite">{{ station.favorite ? 'Undo Favorite' : 'Favorite' }}</MenuItem>
    <Separator />
    <MenuItem v-if="allowEdit" @click="requestEditForm">Edit…</MenuItem>
    <MenuItem v-if="allowDelete" @click="maybeDelete">Delete</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { onMounted, ref, toRefs } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { eventBus } from '@/utils/eventBus'
import { radioStationStore } from '@/stores/radioStationStore'
import { playback } from '@/services/playbackManager'
import { usePolicies } from '@/composables/usePolicies'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'

const props = defineProps<{ station: RadioStation }>()
const { station } = toRefs(props)

const { MenuItem, Separator, trigger } = useContextMenu()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { currentUserCan } = usePolicies()

const allowEdit = ref(false)
const allowDelete = ref(false)

const togglePlayback = () => trigger(async () => {
  const playbackService = playback('radio')

  if (station.value.playback_state === 'Playing') {
    await playbackService.stop()
  } else {
    await playbackService.play(station.value)
  }
})

const toggleFavorite = () => trigger(() => radioStationStore.toggleFavorite(station.value))

const requestEditForm = () => trigger(() => eventBus.emit('MODAL_SHOW_EDIT_RADIO_STATION_FORM', station.value))

const maybeDelete = () => trigger(async () => {
  if (await showConfirmDialog('Delete the radio station? This action is NOT reversible!')) {
    await radioStationStore.delete(station.value)
    toastSuccess(`Radio station deleted.`)
  }
})

onMounted(async () => {
  allowEdit.value = await currentUserCan.editRadioStation(station.value)
  allowDelete.value = await currentUserCan.deleteRadioStation(station.value)
})
</script>
