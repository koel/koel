<template>
  <a
    :href="url('artists.show', { id: artist.id })"
    class="relative flex flex-col justify-end aspect-[3/4] p-5 overflow-hidden rounded-xl border border-k-fg-10 bg-linear-to-b from-k-fg-3 to-k-fg-10 text-center"
  >
    <template v-if="artist.image">
      <img :src="artist.image" alt="" class="absolute inset-0 w-full h-full object-cover" />
      <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/10 to-transparent" />
      <div class="absolute inset-x-0 top-0 h-1/3 bg-linear-to-b from-black/60 to-transparent" />
    </template>
    <img
      v-else
      :src="albumCover || defaultCover"
      alt=""
      class="absolute left-1/2 top-[45%] w-[70%] aspect-square -translate-x-1/2 -translate-y-1/2 rounded-xl shadow-2xl object-cover"
    />

    <span
      :class="{ 'text-white drop-shadow-md': artist.image }"
      class="absolute top-4 left-5 text-5xl font-bold text-k-fg"
    >
      {{ rank }}
    </span>

    <div :class="{ 'text-white': artist.image }" class="relative flex flex-col gap-0.5 text-k-fg">
      <span class="text-lg font-semibold truncate">{{ artist.name }}</span>
      <span class="opacity-80">{{ formatListeningMinutes(listeningTime) }}</span>
    </div>
  </a>
</template>

<script lang="ts" setup>
import { useBranding } from '@/composables/useBranding'
import { useRouter } from '@/composables/useRouter'
import { formatListeningMinutes } from '@/utils/listeningStatistics'

defineProps<{ artist: Artist; rank: number; listeningTime: number; albumCover: string | null }>()

const { url } = useRouter()
const { cover: defaultCover } = useBranding()
</script>
