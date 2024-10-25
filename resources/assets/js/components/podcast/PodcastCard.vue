<template>
  <BaseCard
    :entity="podcast"
    :layout="layout"
    :title="`${podcast.title} by ${podcast.author}`"
    class="cursor-pointer"
    @click="goToPodcast"
  >
    <template #name>
      <a :href="href" class="font-medium" data-testid="title">{{ podcast.title }}</a>
      <span class="text-k-text-secondary">{{ podcast.author }}</span>
    </template>

    <template #thumbnail>
      <img :src="podcast.image" class="aspect-square w-[80px] object-cover rounded-lg" alt="Podcast image">
    </template>
  </BaseCard>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { useRouter } from '@/composables/useRouter'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'

const props = withDefaults(defineProps<{ podcast: Podcast, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { podcast, layout } = toRefs(props)

const { go, url } = useRouter()

const href = url('podcasts.show', { id: podcast.value.id })
const goToPodcast = () => go(href)
</script>
