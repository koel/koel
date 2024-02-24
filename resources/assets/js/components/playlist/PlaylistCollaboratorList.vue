<template>
  <ListSkeleton v-if="loading" />
  <ul v-else>
    <ListItem
      is="li"
      v-for="collaborator in collaborators"
      :role="collaborator.id === playlist.user_id ? 'owner' : 'contributor'"
      :manageable="currentUserIsOwner"
      :removable="currentUserIsOwner && collaborator.id !== playlist.user_id"
      :collaborator="collaborator"
      @remove="removeCollaborator(collaborator)"
    />
  </ul>
</template>

<script setup lang="ts">
import { sortBy } from 'lodash'
import { computed, onMounted, ref, Ref, toRefs } from 'vue'
import { useAuthorization, useDialogBox } from '@/composables'
import { playlistCollaborationService } from '@/services'
import { eventBus, logger } from '@/utils'

import ListSkeleton from '@/components/ui/skeletons/PlaylistCollaboratorListSkeleton.vue'
import ListItem from '@/components/playlist/PlaylistCollaboratorListItem.vue'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const { currentUser } = useAuthorization()
const { showConfirmDialog } = useDialogBox()

let collaborators: Ref<PlaylistCollaborator[]> = ref([])
const loading = ref(false)

const currentUserIsOwner = computed(() => currentUser.value?.id === playlist.value.user_id)

const fetchCollaborators = async () => {
  loading.value = true

  try {
    collaborators.value = sortBy(
      await playlistCollaborationService.fetchCollaborators(playlist.value),
      ({ id }) => {
        if (id === currentUser.value.id) return 0
        if (id === playlist.value.user_id) return 1
        return 2
      }
    )
  } finally {
    loading.value = false
  }
}

const removeCollaborator = async (collaborator: PlaylistCollaborator) => {
  const deadSure = await showConfirmDialog(
    `Remove ${collaborator.name} as a collaborator? This will remove their contributions as well.`
  )

  if (!deadSure) return

  try {
    collaborators.value = collaborators.value.filter(({ id }) => id !== collaborator.id)
    await playlistCollaborationService.removeCollaborator(playlist.value, collaborator)
    eventBus.emit('PLAYLIST_COLLABORATOR_REMOVED', playlist.value)
  } catch (e) {
    logger.error(e)
  }
}

onMounted(async () => await fetchCollaborators())
</script>

<style scoped lang="scss">
ul {
  display: flex;
  width: 100%;
  flex-direction: column;
  margin: 1rem 0;
  gap: .5rem;
}
</style>
