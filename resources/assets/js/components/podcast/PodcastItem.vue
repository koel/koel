<template>
  <a
    :href="url('podcasts.show', { id: podcast.id })"
    class="flex gap-5 p-5 rounded-lg border border-k-fg-5 hover:bg-k-fg-10 bg-k-fg-5 !text-k-fg !hover:text-k-fg"
    data-testid="podcast-item"
    @contextmenu.prevent="onContextMenu"
  >
    <aside class="hidden md:block md:flex-[0_0_128px]">
      <img :src="podcast.image" alt="Podcast image" class="w-[128px] aspect-square object-cover rounded-lg">
    </aside>
    <main class="flex-1">
      <header>
        <h3 class="text-3xl font-bold">
          {{ podcast.title }}
          <FavoriteButton
            v-if="podcast.favorite"
            :favorite="podcast.favorite"
            class="ml-2"
            @toggle="toggleFavorite"
          />
        </h3>
        <p class="mt-2">
          {{ podcast.author }}
          <template v-if="lastPlayedAt"> •
            <span class="text-k-fg-50">
              Last played
              <time :datetime="podcast.last_played_at" :title="podcast.last_played_at">{{ lastPlayedAt }}</time>
            </span>
          </template>
        </p>
      </header>
      <div v-koel-new-tab class="description mt-3 line-clamp-3 text-k-fg-70" v-html="description" />
    </main>
  </a>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import DOMPurify from 'dompurify'
import { formatTimeAgo } from '@vueuse/core'
import { useRouter } from '@/composables/useRouter'
import { podcastStore } from '@/stores/podcastStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { defineAsyncComponent } from '@/utils/helpers'

const { podcast } = defineProps<{ podcast: Podcast }>()
const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))
const PodcastContextMenu = defineAsyncComponent(() => import('@/components/podcast/PodcastContextMenu.vue'))

const { url } = useRouter()
const { openContextMenu } = useContextMenu()

const description = computed(() => DOMPurify.sanitize(podcast.description))

const lastPlayedAt = computed(() => podcast.state.current_episode
  ? formatTimeAgo(new Date(podcast.last_played_at))
  : null,
)

const toggleFavorite = () => podcastStore.toggleFavorite(podcast)

const onContextMenu = (event: MouseEvent) => openContextMenu<'PODCAST'>(PodcastContextMenu, event, {
  podcast,
})
</script>

<style scoped lang="postcss">
.description {
  :deep(p) {
    @apply mb-3;
  }

  :deep(a) {
    @apply text-k-fg hover:text-k-highlight;
  }
}
</style>
