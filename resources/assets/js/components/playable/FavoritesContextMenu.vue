<template>
  <ul>
    <MenuItem v-if="downloadable" @click="download">Download</MenuItem>
    <ContextMenuOfflineItem :playables collection-name="Favorites" />
  </ul>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useDownload } from '@/composables/useDownload'

import ContextMenuOfflineItem from '@/components/ui/context-menu/ContextMenuOfflineItem.vue'

const props = defineProps<{ playables: Playable[] }>()

const { MenuItem, trigger } = useContextMenu()
const { fromFavorites } = useDownload()

const downloadable = computed(
  () =>
    commonStore.state.allows_download &&
    props.playables.length > 0 &&
    (props.playables.length === 1 || commonStore.state.supports_batch_downloading),
)

const download = () => trigger(() => fromFavorites())
</script>
