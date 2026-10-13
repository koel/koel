<template>
  <ContextMenuMusicBrainzItem v-if="song" entity-type="recording" :mbid="song.mbid" />
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { isSong } from '@/utils/typeGuards'

import ContextMenuMusicBrainzItem from '@/components/ui/context-menu/ContextMenuMusicBrainzItem.vue'

const props = defineProps<{ playables: Playable[] }>()

const song = computed(() => (props.playables.length === 1 && isSong(props.playables[0]) ? props.playables[0] : null))
</script>
