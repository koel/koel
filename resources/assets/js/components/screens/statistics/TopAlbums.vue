<template>
  <ol class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-5 gap-6">
    <li v-for="(entry, i) in entries" :key="entry.album.id" class="flex flex-col gap-0.5 min-w-0">
      <a :href="url('albums.show', { id: entry.album.id })" class="mb-2.5">
        <img
          :src="entry.album.cover || defaultCover"
          alt=""
          class="w-full aspect-square rounded-lg border border-k-fg-10 object-cover"
        />
      </a>
      <span class="text-xl font-bold text-k-fg tabular-nums">{{ i + 1 }}</span>
      <a
        :href="url('albums.show', { id: entry.album.id })"
        class="truncate font-medium text-k-fg hover:text-k-highlight"
      >
        {{ entry.album.name }}
      </a>
      <span class="truncate text-k-fg-70">{{ entry.album.artist_name }}</span>
      <span class="text-k-fg-50">{{ formatListeningMinutes(entry.listening_time) }}</span>
    </li>
  </ol>
</template>

<script lang="ts" setup>
import { useBranding } from '@/composables/useBranding'
import { useRouter } from '@/composables/useRouter'
import { formatListeningMinutes } from '@/utils/listeningStatistics'

defineProps<{ entries: ListeningStatistics['top_albums'] }>()

const { url } = useRouter()
const { cover: defaultCover } = useBranding()
</script>
