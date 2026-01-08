<template>
  <slot />
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { logger } from '@/utils/logger'
import { playlistCollaborationService } from '@/services/playlistCollaborationService'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'

const { t } = useI18n()
const { go, url, getRouteParam } = useRouter()

onMounted(async () => {
  try {
    const playlist = await playlistCollaborationService.acceptInvite(getRouteParam('id'))
    go(url('playlists.show', { id: playlist.id }), true)
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error, {
      404: t('screens.acceptCollaborationInvite'),
    })
    logger.error(error)
    go(url('home'))
  }
})
</script>
