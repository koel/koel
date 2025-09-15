<template>
  <span>
    <Btn v-if="shouldShowInviteButton" small success @click.prevent="inviteCollaborators">Invite</Btn>
    <Icon v-if="creatingInviteLink" :icon="faCircleNotch" class="text-k-success" spin />
  </span>
</template>

<script lang="ts" setup>
import { faCircleNotch } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRefs } from 'vue'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import { copyText } from '@/utils/helpers'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const { toastSuccess } = useMessageToaster()

const creatingInviteLink = ref(false)
const shouldShowInviteButton = computed(() => !creatingInviteLink.value)

const inviteCollaborators = async () => {
  creatingInviteLink.value = true

  try {
    await copyText(await playlistCollaborationService.createInviteLink(playlist.value))
    toastSuccess('Invite link copied to clipboard.')
  } finally {
    creatingInviteLink.value = false
  }
}
</script>
