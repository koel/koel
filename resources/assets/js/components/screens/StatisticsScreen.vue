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
      <ListeningSummary :summary="statistics.summary" />

      <PlaysBarChart :bars="playsOverTime" title="Plays over time" />

      <div class="grid md:grid-cols-2 gap-10">
        <PlaysBarChart :bars="playsByWeekday" title="By day of the week" />
        <PlaysBarChart :bars="playsByHour" title="By hour of the day" />
      </div>

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
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useRouter } from '@/composables/useRouter'
import type { ListeningPeriod } from '@/utils/listeningStatistics'
import { getPlaysByHour, getPlaysByWeekday, getPlaysOverTime } from '@/utils/listeningStatistics'

import ScreenBase from '@/components/screens/ScreenBase.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import ListeningSummary from '@/components/screens/statistics/ListeningSummary.vue'
import PlaysBarChart from '@/components/screens/statistics/PlaysBarChart.vue'
import RankedList from '@/components/screens/statistics/RankedList.vue'

const periodOptions: { value: ListeningPeriod; label: string }[] = [
  { value: 'week', label: '7 days' },
  { value: 'month', label: '30 days' },
  { value: 'year', label: '12 months' },
  { value: 'all', label: 'All time' },
]

const { url, onScreenActivated } = useRouter()
const { handleHttpError } = useErrorHandler()

const period = ref<ListeningPeriod>('month')
const statistics = ref<ListeningStatistics | null>(null)
const loading = ref(false)

const hourlyPlays = computed(() => statistics.value?.hourly_plays ?? [])
const playsOverTime = computed(() => getPlaysOverTime(hourlyPlays.value, period.value))
const playsByWeekday = computed(() => getPlaysByWeekday(hourlyPlays.value))
const playsByHour = computed(() => getPlaysByHour(hourlyPlays.value))

const topSongs = computed(() =>
  (statistics.value?.top_songs ?? []).map(({ song, plays }) => ({
    key: song.id,
    title: song.title,
    subtitle: song.artist_name,
    image: song.album_cover,
    href: url('albums.show', { id: song.album_id }),
    plays,
  })),
)

const topArtists = computed(() =>
  (statistics.value?.top_artists ?? []).map(({ artist, plays }) => ({
    key: artist.id,
    title: artist.name,
    image: artist.image,
    href: url('artists.show', { id: artist.id }),
    plays,
  })),
)

const topAlbums = computed(() =>
  (statistics.value?.top_albums ?? []).map(({ album, plays }) => ({
    key: album.id,
    title: album.name,
    subtitle: album.artist_name,
    image: album.cover,
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
