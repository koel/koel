<template>
  <div class="youtube-extra-wrapper overflow-x-hidden">
    <template v-if="videos.length">
      <ul class="space-y-4">
        <li v-for="video in videos" :key="video.id.videoId" data-testid="youtube-video">
          <YouTubeVideo :video="video" />
        </li>
      </ul>
    </template>

    <YouTubeVideoListSkeleton v-if="loading" />

    <div v-if="hasMore && !loading" ref="sentinelRef" class="h-px" />

    <p v-if="somethingWrong">
      Failed to load videos.
      <a href="#" @click.prevent="loadMore">Try again</a>
    </p>
  </div>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, onBeforeUnmount, ref, toRefs, watch } from 'vue'
import { youTubeService } from '@/services/youTubeService'
import { useErrorHandler } from '@/composables/useErrorHandler'

const props = defineProps<{ song: Song }>()
const YouTubeVideo = defineAsyncComponent(() => import('@/components/ui/youtube/YouTubeVideoItem.vue'))
const YouTubeVideoListSkeleton = defineAsyncComponent(
  () => import('@/components/ui/youtube/YouTubeVideoListSkeleton.vue'),
)

const { song } = toRefs(props)

const loading = ref(false)
const videos = ref<YouTubeVideo[]>([])
const hasMore = ref(true)
const sentinelRef = ref<HTMLElement | null>(null)

let nextPageToken = ''

const loadMore = async () => {
  if (loading.value) {
    return
  }

  loading.value = true

  try {
    const result = await youTubeService.searchVideosBySong(song.value, nextPageToken)
    nextPageToken = result.nextPageToken
    hasMore.value = !!nextPageToken
    videos.value.push(...(result.items || []))
  } catch (error: unknown) {
    useErrorHandler().handleHttpError(error)
  } finally {
    loading.value = false
  }
}

const somethingWrong = computed(() => !loading.value && videos.value.length === 0)

let observer: IntersectionObserver | undefined

watch(
  sentinelRef,
  (el, _, onCleanup) => {
    if (!el) {
      return
    }

    if (typeof IntersectionObserver === 'undefined') {
      return
    }

    const obs = new IntersectionObserver(
      entries => {
        if (entries[0].isIntersecting) {
          loadMore()
        }
      },
      { rootMargin: '100px' },
    )

    observer = obs
    obs.observe(el)

    onCleanup(() => obs.disconnect())
  },
  { flush: 'post' },
)

watch(
  song,
  () => {
    videos.value = []
    nextPageToken = ''
    hasMore.value = true
    loadMore()
  },
  { immediate: true },
)

onBeforeUnmount(() => observer?.disconnect())
</script>
