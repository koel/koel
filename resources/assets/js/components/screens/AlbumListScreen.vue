<template>
  <section id="albumsWrapper">
    <ScreenHeader layout="collapsed">
      Albums
      <template #controls>
        <ViewModeSwitch v-model="viewMode" />
      </template>
    </ScreenHeader>

    <div
      ref="scroller"
      v-koel-overflow-fade
      :class="`as-${viewMode}`"
      class="albums main-scroll-wrap"
      data-testid="album-list"
      @scroll="scrolling"
    >
      <template v-if="showSkeletons">
        <AlbumCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
      </template>
      <template v-else>
        <AlbumCard v-for="album in albums" :key="album.id" :album="album" :layout="itemLayout" />
        <ToTopButton />
      </template>
    </div>
  </section>
</template>

<script lang="ts" setup>
import { computed, ref, toRef, watch } from 'vue'
import { albumStore, preferenceStore as preferences } from '@/stores'
import { useInfiniteScroll, useRouter } from '@/composables'

import AlbumCard from '@/components/album/AlbumCard.vue'
import AlbumCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'

const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const albums = toRef(albumStore.state, 'albums')

const {
  ToTopButton,
  scroller,
  scrolling,
  makeScrollable
} = useInfiniteScroll(async () => await fetchAlbums())

watch(viewMode, () => (preferences.albumsViewMode = viewMode.value))

let initialized = false
const loading = ref(false)
const page = ref<number | null>(1)

const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')
const moreAlbumsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && albums.value.length === 0)

const fetchAlbums = async () => {
  if (loading.value || !moreAlbumsAvailable.value) return

  loading.value = true
  page.value = await albumStore.paginate(page.value!)
  loading.value = false
}

useRouter().onScreenActivated('Albums', async () => {
  if (!initialized) {
    viewMode.value = preferences.albumsViewMode || 'thumbnails'
    initialized = true
    await makeScrollable()
  }
})
</script>

<style lang="scss">
#albumsWrapper {
  .albums {
    @include artist-album-wrapper();
  }
}
</style>
`
