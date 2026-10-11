<template>
  <ul>
    <PlaybackItems :playables />
    <AddToItems :playables />
    <RatingItem :playables />
    <RemoveItems :playables />
    <VisibilityItems :playables />
    <ShareItems :playables />
    <ManageItems :playables />
    <MusicBrainzItem :playables />
  </ul>
</template>

<script lang="ts" setup>
import AddToItems from '@/components/playable/context-menu/AddToItems.vue'
import ManageItems from '@/components/playable/context-menu/ManageItems.vue'
import MusicBrainzItem from '@/components/playable/context-menu/MusicBrainzItem.vue'
import PlaybackItems from '@/components/playable/context-menu/PlaybackItems.vue'
import RatingItem from '@/components/playable/context-menu/RatingItem.vue'
import RemoveItems from '@/components/playable/context-menu/RemoveItems.vue'
import ShareItems from '@/components/playable/context-menu/ShareItems.vue'
import VisibilityItems from '@/components/playable/context-menu/VisibilityItems.vue'

defineProps<{ playables: Playable[] }>()
</script>
