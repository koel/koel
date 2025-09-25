<template>
  <slot />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { logger } from '@/utils/logger'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'

const { go, url, getRouteParam } = useRouter()

onMounted(async () => {
  try {
    const playlist = await playlistCollaborationService.acceptInvite(getRouteParam('id'))
    go(url('playlists.show', { id: playlist.id }), true)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error, {
      404: 'The collaboration invite has expired or is invalid.',
    })
    logger.error(error)
    go(url('home'))
  }
})
</script>
