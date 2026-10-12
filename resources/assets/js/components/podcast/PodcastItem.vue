<template>
  <WithGradientBorder
    border-width="1px"
    :color="gradientColor"
    border-color="color-mix(in srgb, var(--color-fg), transparent 97%)"
    class="rounded-lg"
  >
    <a
      :href="url('podcasts.show', { id: podcast.id })"
      :class="{ tinted: coverBackgroundColor }"
      :style="{ backgroundColor: coverBackgroundColor }"
      class="relative flex overflow-hidden rounded-[inherit] bg-k-fg-5 hover:bg-k-fg-10 text-k-fg! hover:text-k-fg!"
      data-testid="podcast-item"
      @contextmenu.prevent="onContextMenu"
    >
      <aside class="hidden md:block md:flex-[0_0_14rem] relative self-stretch">
        <img :src="podcast.image" alt="Podcast image" class="cover-art absolute inset-0 w-full h-full object-cover" />
      </aside>
      <main class="relative flex-1 p-5 md:-ml-8">
        <header>
          <h3 class="text-3xl font-bold">
            {{ podcast.title }}
            <span class="inline-flex items-center gap-2 align-middle text-base font-normal ml-2">
              <FavoriteButton v-if="podcast.favorite" :favorite="podcast.favorite" @toggle="toggleFavorite" />
              <StarRating v-if="podcast.rating" :rateable="podcast" size="xs" />
            </span>
          </h3>
          <p class="mt-2">
            {{ podcast.author }}
            <template v-if="lastPlayedAt">
              •
              <span class="last-played text-k-fg-50">
                Last played
                <time :datetime="podcast.last_played_at" :title="podcast.last_played_at">{{ lastPlayedAt }}</time>
              </span>
            </template>
          </p>
        </header>
        <div v-koel-new-tab class="description mt-3 line-clamp-3 text-k-fg-70" v-html="description" />
      </main>
    </a>
  </WithGradientBorder>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import DOMPurify from 'dompurify'
import { formatTimeAgo } from '@vueuse/core'
import { textToHsl } from '@/utils/formatters'
import { useRouter } from '@/composables/useRouter'
import WithGradientBorder from '@/components/ui/WithGradientBorder.vue'
import StarRating from '@/components/ui/StarRating.vue'
import { podcastStore } from '@/stores/podcastStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { defineAsyncComponent } from '@/utils/helpers'
import { getCoverBackgroundColor } from '@/utils/coverColor'

const { podcast } = defineProps<{ podcast: Podcast }>()
const FavoriteButton = defineAsyncComponent(() => import('@/components/ui/FavoriteButton.vue'))
const PodcastContextMenu = defineAsyncComponent(() => import('@/components/podcast/PodcastContextMenu.vue'))

const { url } = useRouter()
const gradientColor = computed(() => textToHsl(podcast.id))
const { openContextMenu } = useContextMenu()

const description = computed(() => DOMPurify.sanitize(podcast.description))

const lastPlayedAt = computed(() =>
  podcast.state.current_episode ? formatTimeAgo(new Date(podcast.last_played_at)) : null,
)

const coverBackgroundColor = ref<string>()

watch(
  () => podcast.image,
  async image => {
    coverBackgroundColor.value = undefined

    if (!image) {
      return
    }

    const color = await getCoverBackgroundColor(image)

    if (image === podcast.image) {
      coverBackgroundColor.value = color ?? undefined
    }
  },
  { immediate: true },
)

const toggleFavorite = () => podcastStore.toggleFavorite(podcast)

const onContextMenu = (event: MouseEvent) =>
  openContextMenu<'PODCAST'>(PodcastContextMenu, event, {
    podcast,
  })
</script>

<style scoped lang="postcss">
@reference '@css/app.pcss';
.cover-art {
  mask-image: linear-gradient(
    to right,
    black 20%,
    rgb(0 0 0 / 0.9) 35%,
    rgb(0 0 0 / 0.7) 50%,
    rgb(0 0 0 / 0.45) 65%,
    rgb(0 0 0 / 0.22) 80%,
    rgb(0 0 0 / 0.07) 92%,
    transparent 100%
  );
}

.tinted {
  @apply text-white!;

  &:hover {
    @apply brightness-115;
  }

  .last-played,
  .description {
    @apply text-white/70;
  }

  .description :deep(a) {
    @apply text-white;
  }
}

.description {
  :deep(p) {
    @apply mb-3;
  }

  :deep(a) {
    @apply text-k-fg hover:text-k-highlight;
  }
}
</style>
