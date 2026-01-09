<template>
  <HomeScreenSection>
    <template #header>{{ t('screens.topArtists') }}</template>

    <ol v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
      <li v-for="i in 4" :key="i">
        <ArtistCardSkeleton layout="compact" />
      </li>
    </ol>
    <template v-else>
      <ol v-if="artists.length" class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-3">
        <li v-for="artist in artists" :key="artist.id">
          <ArtistCard :artist="artist" layout="compact" />
        </li>
      </ol>
      <p v-else>{{ t('screens.noArtistsFound') }}</p>
    </template>
  </HomeScreenSection>
</template>

<script lang="ts" setup>
import { toRef, toRefs } from 'vue'
import { useI18n } from 'vue-i18n'
import { overviewStore } from '@/stores/overviewStore'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/album-artist/ArtistAlbumCardSkeleton.vue'
import HomeScreenSection from '@/components/screens/home/HomeScreenBlock.vue'

const { t } = useI18n()
const props = withDefaults(defineProps<{ loading?: boolean }>(), { loading: false })
const { loading } = toRefs(props)

const artists = toRef(overviewStore.state, 'mostPlayedArtists')
</script>
