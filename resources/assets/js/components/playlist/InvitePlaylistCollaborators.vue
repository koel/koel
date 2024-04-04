<template>
  <span>
    <Btn v-if="shouldShowInviteButton" green small @click.prevent="inviteCollaborators">Invite</Btn>
    <span v-if="justCreatedInviteLink" class="text-secondary copied">
      <Icon :icon="faCheckCircle" class="text-green" />
      Link copied to clipboard!
    </span>
    <Icon v-if="creatingInviteLink" :icon="faCircleNotch" class="text-green" spin />
  </span>
</template>

<script setup lang="ts">
import { faCheckCircle, faCircleNotch } from '@fortawesome/free-solid-svg-icons'
import { computed, ref, toRefs } from 'vue'
import { copyText } from '@/utils'
import { playlistCollaborationService } from '@/services'

import Btn from '@/components/ui/Btn.vue'

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

<style scoped lang="postcss">
.copied {
  font-size: .95rem;
}

svg {
  margin-right: .25rem;
}
</style>
