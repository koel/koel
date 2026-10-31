<template>
  <template v-if="!isCurrentScreen('Favorites')">
    <Separator />
    <MenuItem v-if="allFavorite" @click="removeFromFavorites">Undo Favorite</MenuItem>
    <MenuItem v-else @click="addToFavorites">Favorite</MenuItem>
  </template>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { useContextMenu } from '@/composables/useContextMenu'
import { usePlayableMenuMethods } from '@/composables/usePlayableMenuMethods'
import { useRouter } from '@/composables/useRouter'

const props = defineProps<{ playables: Playable[] }>()
const { playables } = toRefs(props)

const { MenuItem, Separator, closeContextMenu } = useContextMenu()
const { isCurrentScreen } = useRouter()

const { addToFavorites, removeFromFavorites } = usePlayableMenuMethods(playables, closeContextMenu)

const allFavorite = computed(() => playables.value.every(({ favorite }) => favorite))
</script>
