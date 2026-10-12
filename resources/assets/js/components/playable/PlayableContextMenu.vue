<template>
  <ul>
    <component :is="section.component" v-for="section in sections" :key="section.id" :playables />
  </ul>
</template>

<script lang="ts" setup>
import type { Component } from 'vue'
import { computed } from 'vue'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'

import AddToItems from '@/components/playable/context-menu/AddToItems.vue'
import ManageItems from '@/components/playable/context-menu/ManageItems.vue'
import MusicBrainzItem from '@/components/playable/context-menu/MusicBrainzItem.vue'
import PlaybackItems from '@/components/playable/context-menu/PlaybackItems.vue'
import RatingItem from '@/components/playable/context-menu/RatingItem.vue'
import RemoveItems from '@/components/playable/context-menu/RemoveItems.vue'
import ShareItems from '@/components/playable/context-menu/ShareItems.vue'
import VisibilityItems from '@/components/playable/context-menu/VisibilityItems.vue'

export interface PlayableContextMenuSection {
  id: string
  component: Component
}

defineProps<{ playables: Playable[] }>()

const sections = computed(() =>
  applyFilters<PlayableContextMenuSection[]>(Filter.PLAYABLE_CONTEXT_MENU_SECTIONS, [
    { id: 'playback', component: PlaybackItems },
    { id: 'add-to', component: AddToItems },
    { id: 'rating', component: RatingItem },
    { id: 'remove', component: RemoveItems },
    { id: 'visibility', component: VisibilityItems },
    { id: 'share', component: ShareItems },
    { id: 'manage', component: ManageItems },
    { id: 'musicbrainz', component: MusicBrainzItem },
  ]),
)
</script>
