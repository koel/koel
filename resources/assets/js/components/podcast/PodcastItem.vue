<template>
  <a
    data-testid="podcast-item"
    class="flex gap-5 p-5 rounded-lg border border-white/5 hover:bg-white/10 bg-white/5 !text-k-text-primary !hover:text-k-text-primary"
    :href="`#/podcasts/${podcast.id}`"
  >
    <aside class="hidden md:block md:flex-[0_0_128px]">
      <img :src="podcast.image" alt="Podcast image" class="w-[128px] aspect-square object-cover rounded-lg" />
    </aside>
    <main class="flex-1">
      <header>
        <h3 class="text-3xl font-bold">{{ podcast.title }}</h3>
        <p class="mt-2">
          {{ podcast.author }}
          <template v-if="lastPlayedAt"> •
            <span class="opacity-70">
              Last played
              <time :datetime="podcast.last_played_at" :title="podcast.last_played_at">{{ lastPlayedAt }}</time>
            </span>
          </template>
        </p>
      </header>
      <div class="description text-k-text-secondary mt-3 line-clamp-3" v-html="description" v-koel-new-tab />
    </main>
  </a>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import DOMPurify from 'dompurify'
import { formatTimeAgo } from '@vueuse/core'

const { podcast } = defineProps<{ podcast: Podcast }>()

const description = computed(() => DOMPurify.sanitize(podcast.description))

const lastPlayedAt = computed(() => podcast.state.current_episode
  ? formatTimeAgo(new Date(podcast.last_played_at))
  : null
)
</script>

<style scoped lang="postcss">
.description {
  :deep(p) {
    @apply mb-3;
  }

  :deep(a) {
    @apply text-k-text-primary hover:text-k-accent;
  }
}
</style>
