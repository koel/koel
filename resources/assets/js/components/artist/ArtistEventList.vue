<template>
  <section class="p-6">
    <div v-if="loading" class="space-y-4">
      <ArtistEventSkeleton v-for="key in 3" :key />
    </div>

    <template v-else-if="events.length">
      <p v-if="!nearby" class="mb-4 text-k-fg-70" data-testid="events-elsewhere">
        No events near you. Showing events elsewhere.
      </p>
      <ul class="space-y-4">
        <ArtistEventItem v-for="event in events" :key="event.id" :event />
      </ul>
    </template>

    <p v-else data-testid="no-events">No upcoming events.</p>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { artistStore } from '@/stores/artistStore'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { defineAsyncComponent } from '@/utils/helpers'

import ArtistEventSkeleton from '@/components/artist/ArtistEventSkeleton.vue'

const props = defineProps<{ artist: Artist }>()

const ArtistEventItem = defineAsyncComponent(() => import('@/components/artist/ArtistEventItem.vue'))

const { artist } = props

const loading = ref(false)
const events = ref<LiveEvent[]>([])
const nearby = ref(true)

const fetchEvents = async () => {
  loading.value = true

  try {
    const result = await artistStore.fetchEvents(artist)

    events.value = result.events
    nearby.value = result.nearby
  } catch (e: unknown) {
    useErrorHandler('toast').handleHttpError(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchEvents())
</script>
