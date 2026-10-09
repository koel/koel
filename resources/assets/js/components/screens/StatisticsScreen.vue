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

      <div class="grid md:grid-cols-2 gap-10">
        <RankedList :items="topSongs" title="Top songs" />
        <RankedList :items="topArtists" title="Top artists" />
        <RankedList :items="topAlbums" title="Top albums" />
        <RankedList :items="topGenres" title="Top genres" />
      </div>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { ChartColumnIcon } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { listeningStatisticsService } from '@/services/listeningStatisticsService'
import { useBranding } from '@/composables/useBranding'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useRouter } from '@/composables/useRouter'
import type { ListeningMeasure, ListeningPeriod } from '@/utils/listeningStatistics'
import { getListeningByHour, getListeningByWeekday, getListeningOverTime } from '@/utils/listeningStatistics'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import ListeningSummary from '@/components/screens/statistics/ListeningSummary.vue'
import ListeningBarChart from '@/components/screens/statistics/ListeningBarChart.vue'
import RankedList from '@/components/screens/statistics/RankedList.vue'

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
const { cover: defaultCover } = useBranding()

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

const topSongs = computed(() =>
  (statistics.value?.top_songs ?? []).map(({ song, plays }) => ({
    key: song.id,
    title: song.title,
    subtitle: song.artist_name,
    image: song.album_cover || defaultCover,
    href: url('albums.show', { id: song.album_id }),
    plays,
  })),
)

const topArtists = computed(() =>
  (statistics.value?.top_artists ?? []).map(({ artist, plays }) => ({
    key: artist.id,
    title: artist.name,
    image: artist.image || defaultCover,
    href: url('artists.show', { id: artist.id }),
    plays,
  })),
)

const topAlbums = computed(() =>
  (statistics.value?.top_albums ?? []).map(({ album, plays }) => ({
    key: album.id,
    title: album.name,
    subtitle: album.artist_name,
    image: album.cover || defaultCover,
    href: url('albums.show', { id: album.id }),
    plays,
  })),
)

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
