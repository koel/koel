<template>
  <a
    :href="url('albums.show', { id: album.id })"
    class="relative flex flex-col justify-end aspect-[3/4] p-5 overflow-hidden rounded-xl border border-k-fg-10 bg-linear-to-b from-k-fg-3 to-k-fg-10 text-center"
  >
    <img
      :src="album.cover || defaultCover"
      alt=""
      class="absolute left-1/2 top-[45%] w-[70%] aspect-square -translate-x-1/2 -translate-y-1/2 rounded-xl shadow-2xl object-cover"
    />

    <span class="absolute top-4 left-5 text-5xl font-bold text-k-fg">{{ rank }}</span>

    <div class="relative flex flex-col gap-0.5 text-k-fg">
      <span class="text-lg font-semibold truncate">{{ album.name }}</span>
      <span class="truncate opacity-80">{{ album.artist_name }}</span>
      <span class="opacity-80">{{ formatListeningMinutes(listeningTime) }}</span>
    </div>
  </a>
</template>

<script lang="ts" setup>
import { useBranding } from '@/composables/useBranding'
import { useRouter } from '@/composables/useRouter'
import { formatListeningMinutes } from '@/utils/listeningStatistics'

defineProps<{ album: Album; rank: number; listeningTime: number }>()

const { url } = useRouter()
const { cover: defaultCover } = useBranding()
</script>
