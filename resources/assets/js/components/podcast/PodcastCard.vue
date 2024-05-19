<template>
  <BaseCard
    :entity="podcast"
    :layout="layout"
    :title="`${podcast.title} by ${podcast.author}`"
    @click="goToPodcast"
    class="cursor-pointer"
  >
    <template #name>
      <a :href="`#/podcasts/${podcast.id}`" class="font-medium" data-testid="title">{{ podcast.title }}</a>
      <span class="text-k-text-secondary">{{ podcast.author }}</span>
    </template>

    <template #thumbnail>
      <img :src="podcast.image" class="aspect-square w-[80px] object-cover rounded-lg" alt="Podcast image" />
    </template>
  </BaseCard>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'
import { eventBus } from '@/utils'
import { useRouter } from '@/composables'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'

const props = withDefaults(defineProps<{ podcast: Podcast, layout?: ArtistAlbumCardLayout }>(), { layout: 'full' })
const { podcast, layout } = toRefs(props)

const { go } = useRouter()

const goToPodcast = () => go(`/podcasts/${podcast.value.id}`)
</script>
