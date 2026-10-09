<template>
  <ol :style="{ gridTemplateRows: `repeat(${rowCount}, auto)` }" class="grid md:grid-flow-col md:grid-cols-3 gap-x-8">
    <li
      v-for="(entry, i) in entries"
      :key="entry.song.id"
      :class="{ 'last-in-column': isLastInColumn(i) }"
      class="flex items-start gap-3 py-2.5 border-b border-k-fg-5"
    >
      <img :src="entry.song.album_cover || defaultCover" alt="" class="w-12 h-12 rounded-md object-cover" />
      <span class="w-5 text-right font-medium text-k-fg tabular-nums">{{ i + 1 }}</span>
      <div class="flex-1 min-w-0">
        <a
          :href="url('albums.show', { id: entry.song.album_id })"
          class="block truncate font-medium text-k-fg hover:text-k-highlight"
        >
          {{ entry.song.title }}
        </a>
        <p class="truncate text-k-fg-70">{{ entry.song.artist_name }} · {{ pluralize(entry.plays, 'play') }}</p>
      </div>
    </li>
  </ol>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useBranding } from '@/composables/useBranding'
import { useRouter } from '@/composables/useRouter'
import { pluralize } from '@/utils/formatters'

const props = defineProps<{ entries: ListeningStatistics['top_songs'] }>()

const COLUMN_COUNT = 3

const { url } = useRouter()
const { cover: defaultCover } = useBranding()

const rowCount = computed(() => Math.ceil(props.entries.length / COLUMN_COUNT))

const isLastInColumn = (index: number) => (index + 1) % rowCount.value === 0 || index === props.entries.length - 1
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

@media (min-width: 768px) {
  .last-in-column {
    @apply border-b-transparent;
  }
}
</style>
