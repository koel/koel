<template>
  <VirtualGridScroller
    ref="scroller"
    :items="artists"
    :min-item-width="240"
    class="p-6 gap-x-5 gap-y-5"
    data-testid="artist-grid"
    @scrolled-to-end="emit('scrolled-to-end')"
  >
    <template #default="{ item }: { item: Artist }">
      <ArtistCard :artist="item" />
    </template>
  </VirtualGridScroller>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

import VirtualGridScroller from '@/components/ui/VirtualGridScroller.vue'
import ArtistCard from '@/components/artist/ArtistCard.vue'

defineProps<{ artists: Artist[] }>()

const emit = defineEmits<{ (e: 'scrolled-to-end'): void }>()

const scroller = ref<InstanceType<typeof VirtualGridScroller>>()

const scrollToTop = () => scroller.value?.scrollToTop()

defineExpose({ scrollToTop })
</script>
