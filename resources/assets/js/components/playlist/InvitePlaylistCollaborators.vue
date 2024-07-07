<template>
  <span>
    <Btn v-if="shouldShowInviteButton" small success @click.prevent="inviteCollaborators">Invite</Btn>
    <span v-if="justCreatedInviteLink" class="text-k-text-secondary text-[0.95rem]">
      <Icon :icon="faCheckCircle" class="text-k-success mr-1" />
      Link copied to clipboard!
    </span>
    <Icon v-if="creatingInviteLink" :icon="faCircleNotch" class="text-k-success" spin />
  </span>
</template>

<script lang="ts" setup>
import { faCheckCircle, faCircleNotch } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRefs } from 'vue'
import { copyText } from '@/utils'
import { playlistCollaborationService } from '@/services'

import Btn from '@/components/ui/form/Btn.vue'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const creatingInviteLink = ref(false)
const justCreatedInviteLink = ref(false)
const shouldShowInviteButton = computed(() => !creatingInviteLink.value && !justCreatedInviteLink.value)

const inviteCollaborators = async () => {
  creatingInviteLink.value = true

  try {
    await copyText(await playlistCollaborationService.createInviteLink(playlist.value))
    justCreatedInviteLink.value = true
    setTimeout(() => (justCreatedInviteLink.value = false), 5_000)
  } finally {
    creatingInviteLink.value = false
  }
}
</script>
