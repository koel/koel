<template>
  <span>
    <Btn v-if="shouldShowInviteButton" small success @click.prevent="inviteCollaborators">{{ t('playlists.invite') }}</Btn>
    <Icon v-if="creatingInviteLink" :icon="faCircleNotch" class="text-k-success" spin />
  </span>
</template>

<script lang="ts" setup>
import { faCircleNotch } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import { copyText } from '@/utils/helpers'
import { useMessageToaster } from '@/composables/useMessageToaster'

import Btn from '@/components/ui/form/Btn.vue'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const { t } = useI18n()
const { toastSuccess } = useMessageToaster()

const creatingInviteLink = ref(false)
const shouldShowInviteButton = computed(() => !creatingInviteLink.value)

const inviteCollaborators = async () => {
  creatingInviteLink.value = true

  try {
    await copyText(await playlistCollaborationService.createInviteLink(playlist.value))
    toastSuccess(t('playlists.inviteLink'))
  } finally {
    creatingInviteLink.value = false
  }
}
</script>
