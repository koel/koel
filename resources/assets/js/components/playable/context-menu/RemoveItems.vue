<template>
  <template v-if="isCurrentScreen('Queue')">
    <Separator />
    <MenuItem @click="removeFromQueue">Remove from Queue</MenuItem>
    <Separator />
  </template>

  <template v-if="isCurrentScreen('Favorites')">
    <Separator />
    <MenuItem @click="removeFromFavorites">Remove from Favorites</MenuItem>
  </template>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { usePlayableMenuMethods } from '@/composables/usePlayableMenuMethods'
import { useRouter } from '@/composables/useRouter'

const props = defineProps<{ playables: Playable[] }>()
const { playables } = toRefs(props)

const { MenuItem, Separator, closeContextMenu } = useContextMenu()
const { isCurrentScreen } = useRouter()

const { removeFromQueue, removeFromFavorites } = usePlayableMenuMethods(playables, closeContextMenu)
</script>
