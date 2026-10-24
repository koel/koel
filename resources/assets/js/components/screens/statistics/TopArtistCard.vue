<template>
  <a
    :href="url('artists.show', { id: artist.id })"
    class="relative flex flex-col justify-end aspect-[3/4] p-5 overflow-hidden rounded-xl text-center text-white"
  >
    <img :src="artwork" alt="" class="absolute inset-0 w-full h-full object-cover" />
    <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/10 to-transparent" />
    <div class="absolute inset-x-0 top-0 h-1/3 bg-linear-to-b from-black/60 to-transparent" />

    <span class="absolute top-4 left-5 text-5xl font-bold drop-shadow-md">{{ rank }}</span>

    <div class="relative flex flex-col gap-0.5">
      <span class="text-lg font-semibold truncate">{{ artist.name }}</span>
      <span class="opacity-80">{{ formatListeningMinutes(listeningTime) }}</span>
    </div>
  </a>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useBranding } from '@/composables/useBranding'
import { useRouter } from '@/composables/useRouter'
import { formatListeningMinutes } from '@/utils/listeningStatistics'

const props = defineProps<{ artist: Artist; rank: number; listeningTime: number; albumCover: string | null }>()

const { url } = useRouter()
const { cover: defaultCover } = useBranding()

const artwork = computed(() => props.artist.image || props.albumCover || defaultCover)
</script>
