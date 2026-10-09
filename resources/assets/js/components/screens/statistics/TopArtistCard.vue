<template>
  <a
    :href="url('artists.show', { id: artist.id })"
    class="relative flex flex-col justify-end aspect-[3/4] p-4 overflow-hidden rounded-xl border border-k-fg-10 bg-k-fg-5 text-center"
  >
    <template v-if="artist.image">
      <img :src="artist.image" alt="" class="absolute inset-0 w-full h-full object-cover" />
      <div class="absolute inset-0 bg-linear-to-t from-black/80 via-black/10 to-transparent" />
    </template>
    <img
      v-else
      :src="defaultCover"
      alt=""
      class="absolute left-1/2 top-1/2 w-3/5 aspect-square -translate-x-1/2 -translate-y-1/2 rounded-lg shadow-lg object-cover"
    />

    <span :class="{ 'text-white': artist.image }" class="absolute top-3 left-4 text-5xl font-bold text-k-fg">
      {{ rank }}
    </span>

    <div :class="{ 'text-white': artist.image }" class="relative flex flex-col gap-0.5 text-k-fg">
      <span class="font-semibold truncate">{{ artist.name }}</span>
      <span class="text-sm opacity-80">{{ formatListeningMinutes(listeningTime) }}</span>
    </div>
  </a>
</template>

<script lang="ts" setup>
import { useBranding } from '@/composables/useBranding'
import { useRouter } from '@/composables/useRouter'
import { formatListeningMinutes } from '@/utils/listeningStatistics'

defineProps<{ artist: Artist; rank: number; listeningTime: number }>()

const { url } = useRouter()
const { cover: defaultCover } = useBranding()
</script>
