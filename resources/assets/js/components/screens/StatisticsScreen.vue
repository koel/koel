<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">Statistics</ScreenHeader>
    </template>

    <div class="flex flex-col flex-1 gap-14">
      <section
        v-if="loadingLibraryStats || libraryStats.length"
        :aria-busy="loadingLibraryStats"
        aria-labelledby="statistics-storage"
        class="flex flex-col gap-6"
      >
        <h2 id="statistics-storage" class="text-3xl font-thin text-k-fg">Storage</h2>
        <div
          v-if="loadingLibraryStats"
          class="grid grid-cols-2 md:grid-cols-4 gap-4"
          data-testid="library-stats-skeleton"
        >
          <StatTileSkeleton v-for="i in 4" :key="i" />
        </div>
        <LibraryStats v-else :stats="libraryStats" />
      </section>

      <section aria-labelledby="statistics-listening" class="flex flex-col flex-1 gap-10">
        <h2 id="statistics-listening" class="text-3xl font-thin text-k-fg">Listening</h2>
        <SegmentedControl v-model="period" :options="periodOptions" class="self-start" name="listening-period" />

        <div v-if="loading" aria-busy="true" class="flex flex-col gap-10" data-testid="statistics-skeleton">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <StatTileSkeleton v-for="i in 8" :key="i" />
          </div>
          <div class="skeleton">
            <div class="pulse h-64 rounded-md" />
          </div>
        </div>

        <ScreenEmptyState v-else-if="!statistics?.summary.plays" data-testid="statistics-empty">
          <template #icon>
            <ChartColumnIcon :size="96" />
          </template>
          No plays in this period.
        </ScreenEmptyState>

        <div v-else class="flex flex-col gap-10" data-testid="statistics">
          <ListeningSummary :statistics />

          <section class="flex flex-col gap-6">
            <SegmentedControl v-model="measure" :options="measureOptions" class="self-start" name="listening-measure" />
            <ListeningBarChart :bars="listeningOverTime" :measure title="Over time" />

            <div class="grid md:grid-cols-2 gap-10">
              <ListeningBarChart :bars="listeningByWeekday" :measure title="By day of the week" />
              <ListeningBarChart :bars="listeningByHour" :measure title="By hour of the day" />
            </div>
          </section>

          <ShelfSection v-if="statistics.top_artists.length">
            <template #header>Your Top Artists</template>
            <Carousel>
              <TopArtistCard
                v-for="(entry, i) in statistics.top_artists"
                :key="entry.artist.id"
                :album-cover="entry.album_cover"
                :artist="entry.artist"
                :listening-time="entry.listening_time"
                :rank="i + 1"
              />
            </Carousel>
          </ShelfSection>

          <ShelfSection v-if="statistics.top_songs.length">
            <template #header>Your Top Songs</template>
            <RankedColumns :items="topSongs" />
          </ShelfSection>

          <ShelfSection v-if="statistics.top_albums.length">
            <template #header>Your Top Albums</template>
            <Carousel>
              <TopAlbumCard
                v-for="(entry, i) in statistics.top_albums"
                :key="entry.album.id"
                :album="entry.album"
                :listening-time="entry.listening_time"
                :rank="i + 1"
              />
            </Carousel>
          </ShelfSection>

          <ShelfSection v-if="topGenres.length">
            <template #header>Your Top Genres</template>
            <RankedColumns :items="topGenres" />
          </ShelfSection>
        </div>
      </section>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { ChartColumnIcon } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { statsService } from '@/services/statsService'
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
import ShelfSection from '@/components/ui/ShelfSection.vue'
import LibraryStats from '@/components/screens/statistics/LibraryStats.vue'
import StatTileSkeleton from '@/components/screens/statistics/StatTileSkeleton.vue'
import ListeningSummary from '@/components/screens/statistics/ListeningSummary.vue'
import ListeningBarChart from '@/components/screens/statistics/ListeningBarChart.vue'
import RankedColumns from '@/components/screens/statistics/RankedColumns.vue'
import TopAlbumCard from '@/components/screens/statistics/TopAlbumCard.vue'
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
const libraryStats = ref<LibraryStat[]>([])
const loadingLibraryStats = ref(true)
const loading = ref(false)

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
    image: song.album_small_cover || defaultCover,
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

let latestRequestId = 0

const fetchStatistics = async () => {
  const requestId = ++latestRequestId
  const isLatestRequest = () => requestId === latestRequestId

  loading.value = true

  try {
    const result = await statsService.fetchListeningStats(period.value)

    if (isLatestRequest()) {
      statistics.value = result
    }
  } catch (error: unknown) {
    if (isLatestRequest()) {
      handleHttpError(error)
    }
  } finally {
    if (isLatestRequest()) {
      loading.value = false
    }
  }
}

const fetchLibraryStats = async () => {
  try {
    libraryStats.value = await statsService.fetchLibraryStats()
  } catch (error: unknown) {
    handleHttpError(error)
  } finally {
    loadingLibraryStats.value = false
  }
}

watch(period, fetchStatistics)

onScreenActivated('Statistics', () => {
  fetchLibraryStats()
  fetchStatistics()
})
</script>
