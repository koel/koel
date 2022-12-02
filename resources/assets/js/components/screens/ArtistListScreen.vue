<template>
  <section id="artistsWrapper">
    <ScreenHeader layout="collapsed">
      Artists
      <template #controls>
        <ViewModeSwitch v-model="viewMode" />
      </template>
    </ScreenHeader>

    <div
      ref="scroller"
      :class="`as-${viewMode}`"
      class="artists main-scroll-wrap"
      data-testid="artist-list"
      @scroll="scrolling"
    >
      <template v-if="showSkeletons">
        <ArtistCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
      </template>
      <template v-else>
        <ArtistCard v-for="artist in artists" :key="artist.id" :artist="artist" :layout="itemLayout" />
        <ToTopButton />
      </template>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { computed, ref, toRef, watch } from 'vue'
import { artistStore, preferenceStore as preferences } from '@/stores'
import { useInfiniteScroll, useRouter } from '@/composables'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'

const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const artists = toRef(artistStore.state, 'artists')

const {
  ToTopButton,
  scroller,
  scrolling,
  makeScrollable
} = useInfiniteScroll(async () => await fetchArtists())

watch(viewMode, () => preferences.artistsViewMode = viewMode.value)

let initialized = false
const loading = ref(false)
const page = ref<number | null>(1)

const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')
const moreArtistsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && artists.value.length === 0)

const fetchArtists = async () => {
  if (loading.value || !moreArtistsAvailable.value) return

  loading.value = true
  page.value = await artistStore.paginate(page.value!)
  loading.value = false
}

useRouter().onScreenActivated('Artists', async () => {
  if (!initialized) {
    viewMode.value = preferences.artistsViewMode || 'thumbnails'
    initialized = true
    await makeScrollable()
  }
})
</script>

<style lang="scss">
#artistsWrapper {
  .artists {
    @include artist-album-wrapper();
  }
}
</style>
