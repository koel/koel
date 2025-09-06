<template>
  <section class="p-6">
    <div v-if="loading" class="space-y-4">
      <ArtistEventSkeleton v-for="key in 3" :key />
    </div>

    <template v-else>
      <ul v-if="events?.length" class="space-y-4">
        <ArtistEventItem v-for="event in events" :key="event.id" :event />
      </ul>
      <p v-else class="text-k-text-secondary">No upcoming events.</p>
    </template>
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

const fetchEvents = async () => {
  loading.value = true

  try {
    events.value = await artistStore.fetchEvents(artist)
  } catch (e: unknown) {
    useErrorHandler('toast').handleHttpError(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchEvents())
</script>
