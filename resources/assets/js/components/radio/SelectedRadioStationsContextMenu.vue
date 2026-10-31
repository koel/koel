<template>
  <ul>
    <MenuItem v-if="allFavorite" @click="undoFavorite">Undo Favorite</MenuItem>
    <MenuItem v-else @click="favorite">Favorite</MenuItem>
  </ul>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { radioStationStore } from '@/stores/radioStationStore'
import { useContextMenu } from '@/composables/useContextMenu'

const props = defineProps<{ stations: RadioStation[] }>()
const { stations } = toRefs(props)

const { MenuItem, trigger } = useContextMenu()

const allFavorite = computed(() => stations.value.every(station => station.favorite))

const favorite = () => trigger(() => radioStationStore.favorite(stations.value))
const undoFavorite = () => trigger(() => radioStationStore.undoFavorite(stations.value))
</script>
