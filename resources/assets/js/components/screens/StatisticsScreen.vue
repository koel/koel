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

      <StatisticsBlock v-if="statistics.top_artists.length">
        <template #header>Your Top Artists</template>
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
      </StatisticsBlock>

      <StatisticsBlock v-if="statistics.top_songs.length">
        <template #header>Your Top Songs</template>
        <RankedColumns :items="topSongs" />
      </StatisticsBlock>

      <StatisticsBlock v-if="statistics.top_albums.length">
        <template #header>Your Top Albums</template>
        <TopAlbums :entries="statistics.top_albums" />
      </StatisticsBlock>

      <StatisticsBlock v-if="topGenres.length">
        <template #header>Your Top Genres</template>
        <RankedColumns :items="topGenres" />
      </StatisticsBlock>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { ChartColumnIcon } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { listeningStatisticsService } from '@/services/listeningStatisticsService'
import { pluralize } from '@/utils/formatters'
import { useBranding } from '@/composables/useBranding'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useRouter } from '@/composables/useRouter'
import type { ListeningMeasure, ListeningPeriod } from '@/utils/listeningStatistics'
import {
  formatListeningMinutes,
  getListeningByHour,
  getListeningByWeekday,
  getListeningOverTime,
} from '@/utils/listeningStatistics'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import Carousel from '@/components/ui/Carousel.vue'
import ListeningSummary from '@/components/screens/statistics/ListeningSummary.vue'
import ListeningBarChart from '@/components/screens/statistics/ListeningBarChart.vue'
import RankedColumns from '@/components/screens/statistics/RankedColumns.vue'
import StatisticsBlock from '@/components/screens/statistics/StatisticsBlock.vue'
import TopAlbums from '@/components/screens/statistics/TopAlbums.vue'
import TopArtistCard from '@/components/screens/statistics/TopArtistCard.vue'

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
    subtitle: `${song.artist_name} · ${pluralize(plays, 'play')}`,
    href: url('albums.show', { id: song.album_id }),
    image: song.album_cover || defaultCover,
  })),
)

const topGenres = computed(() =>
  (statistics.value?.top_genres ?? []).map(({ id, name, listening_time }) => ({
    key: id,
    title: name,
    subtitle: formatListeningMinutes(listening_time),
    href: url('genres.show', { id }),
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
