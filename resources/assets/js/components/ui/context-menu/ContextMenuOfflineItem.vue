<template>
  <template v-if="swReady && hasOfflineCapablePlayables(playables)">
    <Separator />
    <MenuItem @click="toggleOffline">{{ allCached ? 'Remove Offline Versions' : 'Make Available Offline' }}</MenuItem>
  </template>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { useOfflinePlayback } from '@/composables/useOfflinePlayback'

const props = defineProps<{
  playables: Playable[]
  collectionName: string
}>()

const { MenuItem, Separator, trigger } = useContextMenu()
const { swReady, allPlayablesCached, hasOfflineCapablePlayables, togglePlayablesOffline } = useOfflinePlayback()

const allCached = computed(() => allPlayablesCached(props.playables))

const toggleOffline = () => trigger(() => togglePlayablesOffline(props.playables, `"${props.collectionName}"`))
</script>
