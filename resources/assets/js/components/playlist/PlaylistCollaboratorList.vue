<template>
  <ListSkeleton v-if="loading" />
  <ul v-else class="w-full space-y-3">
    <ListItem
      v-for="collaborator in collaborators"
      :key="collaborator.id"
      :collaborator="collaborator"
      :manageable="currentUserIsOwner"
      :removable="currentUserIsOwner && collaborator.id !== playlist.user_id"
      :role="collaborator.id === playlist.user_id ? 'owner' : 'contributor'"
      @remove="removeCollaborator(collaborator)"
    />
  </ul>
</template>

<script setup lang="ts">
import { sortBy } from 'lodash'
import { computed, onMounted, ref, Ref, toRefs } from 'vue'
import { useAuthorization, useDialogBox, useErrorHandler } from '@/composables'
import { playlistCollaborationService } from '@/services'
import { eventBus } from '@/utils'

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
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  }
}

onMounted(async () => await fetchCollaborators())
</script>
