<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Listening Statistics

        <template #controls>
          <SegmentedControl v-model="period" :options="periodOptions" name="listening-period" />
        </template>
      </ScreenHeader>
    </template>

    <p v-if="loading" class="text-k-fg-70" role="status">Loading…</p>

    <ScreenEmptyState v-else-if="!statistics?.summary.plays" data-testid="statistics-empty">
      <template #icon>
        <ChartColumnIcon :size="96" />
      </template>
      No plays in this period.
    </ScreenEmptyState>

    <div v-else class="flex flex-col gap-10" data-testid="statistics">
      <ListeningSummary :period-label="periodLabel" :statistics />

      <section class="flex flex-col gap-6">
        <SegmentedControl v-model="measure" :options="measureOptions" class="self-start" name="listening-measure" />
        <ListeningBarChart :bars="listeningOverTime" :measure title="Over time" />

        <div class="grid md:grid-cols-2 gap-10">
          <ListeningBarChart :bars="listeningByWeekday" :measure title="By day of the week" />
          <ListeningBarChart :bars="listeningByHour" :measure title="By hour of the day" />
        </div>
      </section>

      <section v-if="statistics.top_artists.length" class="flex flex-col gap-4">
        <h3 class="text-xl font-semibold text-k-fg">Your Top Artists</h3>
        <Carousel showcase>
          <TopArtistCard
            v-for="(entry, i) in statistics.top_artists"
            :key="entry.artist.id"
            :album-cover="entry.album_cover"
            :artist="entry.artist"
            :listening-time="entry.listening_time"
            :rank="i + 1"
          />
        </Carousel>
      </section>

      <section v-if="statistics.top_songs.length" class="flex flex-col gap-4">
        <h3 class="text-xl font-semibold text-k-fg">Your Top Songs</h3>
        <TopSongs :entries="statistics.top_songs" />
      </section>

      <section v-if="statistics.top_albums.length" class="flex flex-col gap-4">
        <h3 class="text-xl font-semibold text-k-fg">Your Top Albums</h3>
        <TopAlbums :entries="statistics.top_albums" />
      </section>

      <section v-if="topGenres.length" class="flex flex-col gap-4">
        <h3 class="text-xl font-semibold text-k-fg">Your Top Genres</h3>
        <RankedList :items="topGenres" class="max-w-xl" />
      </section>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { ChartColumnIcon } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { listeningStatisticsService } from '@/services/listeningStatisticsService'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useRouter } from '@/composables/useRouter'
import type { ListeningMeasure, ListeningPeriod } from '@/utils/listeningStatistics'
import { getListeningByHour, getListeningByWeekday, getListeningOverTime } from '@/utils/listeningStatistics'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import Carousel from '@/components/ui/Carousel.vue'
import ListeningSummary from '@/components/screens/statistics/ListeningSummary.vue'
import ListeningBarChart from '@/components/screens/statistics/ListeningBarChart.vue'
import RankedList from '@/components/screens/statistics/RankedList.vue'
import TopAlbums from '@/components/screens/statistics/TopAlbums.vue'
import TopArtistCard from '@/components/screens/statistics/TopArtistCard.vue'
import TopSongs from '@/components/screens/statistics/TopSongs.vue'

const PERIOD_LABELS: Record<ListeningPeriod, string> = {
  week: '7 days',
  month: '30 days',
  year: '12 months',
  all: 'All time',
}

const periodOptions = (Object.keys(PERIOD_LABELS) as ListeningPeriod[]).map(value => ({
  value,
  label: PERIOD_LABELS[value],
}))

const { url, onScreenActivated } = useRouter()
const { handleHttpError } = useErrorHandler()

const period = ref<ListeningPeriod>('month')
const statistics = ref<ListeningStatistics | null>(null)
const loading = ref(false)

const periodLabel = computed(() => PERIOD_LABELS[period.value])

const measureOptions: { value: ListeningMeasure; label: string }[] = [
  { value: 'plays', label: 'Plays' },
  { value: 'minutes', label: 'Minutes' },
]

const measure = ref<ListeningMeasure>('plays')

const hourlyListening = computed(() => statistics.value?.hourly_listening ?? [])
const listeningOverTime = computed(() => getListeningOverTime(hourlyListening.value, period.value, measure.value))
const listeningByWeekday = computed(() => getListeningByWeekday(hourlyListening.value, measure.value))
const listeningByHour = computed(() => getListeningByHour(hourlyListening.value, measure.value))

const topGenres = computed(() =>
  (statistics.value?.top_genres ?? []).map(({ id, name, plays }) => ({
    key: id,
    title: name,
    href: url('genres.show', { id }),
    plays,
  })),
)

const fetchStatistics = async () => {
  loading.value = true

  try {
    statistics.value = await listeningStatisticsService.fetch(period.value)
  } catch (error: unknown) {
    handleHttpError(error)
  } finally {
    loading.value = false
  }
}

watch(period, fetchStatistics)

onScreenActivated('Statistics', fetchStatistics)
</script>
