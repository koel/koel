<template>
  <BaseCard
    :entity="podcast"
    :layout="layout"
    :title="`${podcast.title} by ${podcast.author}`"
    class="cursor-pointer"
    @click="goToPodcast"
    @contextmenu.prevent="onContextMenu"
  >
    <template #name>
      <h3>
        <a :href class="font-medium" data-testid="title">{{ podcast.title }}</a>

        <FavoriteButton
          v-if="podcast.favorite"
          :favorite="podcast.favorite"
          class="ml-2"
          @toggle="toggleFavorite"
        />
      </h3>
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
import { podcastStore } from '@/stores/podcastStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'

import BaseCard from '@/components/ui/album-artist/AlbumOrArtistCard.vue'
import FavoriteButton from '@/components/ui/FavoriteButton.vue'

const props = withDefaults(defineProps<{ podcast: Podcast, layout?: CardLayout }>(), { layout: 'full' })

const ContextMenu = defineAsyncComponent(() => import('@/components/podcast/PodcastContextMenu.vue'))

const { podcast, layout } = toRefs(props)

const { openContextMenu } = useContextMenu()
const { go, url } = useRouter()

const href = url('podcasts.show', { id: podcast.value.id })
const goToPodcast = () => go(href)

const onContextMenu = (event: MouseEvent) => {
  openContextMenu<'PODCAST'>(ContextMenu, event, {
    podcast: podcast.value,
  })
}

const toggleFavorite = () => podcastStore.toggleFavorite(podcast.value)
</script>
