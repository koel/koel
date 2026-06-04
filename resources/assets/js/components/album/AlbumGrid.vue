<template>
  <VirtualGridScroller
    ref="scroller"
    :items="albums"
    :min-item-width="240"
    class="p-6 gap-x-5 gap-y-5"
    data-testid="album-grid"
    @scrolled-to-end="emit('scrolled-to-end')"
  >
    <template #default="{ item }: { item: Album }">
      <AlbumCard :album="item" :show-release-year />
    </template>
  </VirtualGridScroller>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

import VirtualGridScroller from '@/components/ui/VirtualGridScroller.vue'
import AlbumCard from '@/components/album/AlbumCard.vue'

withDefaults(defineProps<{ albums: Album[]; showReleaseYear?: boolean }>(), {
  showReleaseYear: false,
})

const emit = defineEmits<{ (e: 'scrolled-to-end'): void }>()

const scroller = ref<InstanceType<typeof VirtualGridScroller>>()

const scrollToTop = () => scroller.value?.scrollToTop()

defineExpose({ scrollToTop })
</script>
