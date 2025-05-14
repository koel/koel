<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Artists
        <template #controls>
          <div v-if="!loading" class="flex gap-2">
            <ArtistListSorter :field="sortParams.field" :order="sortParams.order" @sort="sort" />
            <ViewModeSwitch v-model="viewMode" />
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      <template #icon>
        <Icon :icon="faMicrophoneSlash" />
      </template>
      No artists found.
      <span class="secondary block">
        {{ isAdmin ? 'Have you set up your library yet?' : 'Contact your administrator to set up your library.' }}
      </span>
    </ScreenEmptyState>

    <div v-else ref="gridContainer" v-koel-overflow-fade class="-m-6 overflow-auto">
      <ArtistGrid :view-mode="viewMode" data-testid="artist-list">
        <template v-if="showSkeletons">
          <ArtistCardSkeleton v-for="i in 10" :key="i" :layout="itemLayout" />
        </template>
        <template v-else>
          <ArtistCard v-for="artist in artists" :key="artist.id" :artist="artist" :layout="itemLayout" />
          <ToTopButton />
        </template>
      </ArtistGrid>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faMicrophoneSlash } from '@fortawesome/free-solid-svg-icons'
import { computed, nextTick, reactive, ref, toRef, watch } from 'vue'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { useRouter } from '@/composables/useRouter'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'
import { useAuthorization } from '@/composables/useAuthorization'

import ArtistCard from '@/components/artist/ArtistCard.vue'
import ArtistCardSkeleton from '@/components/ui/skeletons/ArtistAlbumCardSkeleton.vue'
import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ViewModeSwitch from '@/components/ui/ViewModeSwitch.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import ArtistGrid from '@/components/ui/album-artist/AlbumOrArtistGrid.vue'
import ArtistListSorter from '@/components/artist/ArtistListSorter.vue'

const { isAdmin } = useAuthorization()

const gridContainer = ref<HTMLDivElement>()
const grid = ref<InstanceType<typeof ArtistGrid>>()
const viewMode = ref<ArtistAlbumViewMode>('thumbnails')
const artists = toRef(artistStore.state, 'artists')

const sortParams = reactive<{ field: ArtistListSortField, order: SortOrder }>({
  field: 'name',
  order: 'asc',
})

let initialized = false
const loading = ref(false)
const page = ref<number | null>(1)

const libraryEmpty = computed(() => commonStore.state.song_length === 0)
const itemLayout = computed<ArtistAlbumCardLayout>(() => viewMode.value === 'thumbnails' ? 'full' : 'compact')
const moreArtistsAvailable = computed(() => page.value !== null)
const showSkeletons = computed(() => loading.value && artists.value.length === 0)

const fetchArtists = async () => {
  if (loading.value || !moreArtistsAvailable.value) {
    return
  }

  loading.value = true

  page.value = await artistStore.paginate({
    page: page!.value || 1,
    sort: sortParams.field,
    order: sortParams.order,
  })

  loading.value = false
}

const {
  ToTopButton,
  makeScrollable,
} = useInfiniteScroll(gridContainer, async () => await fetchArtists())

const resetState = async () => {
  page.value = 1

  artistStore.reset()
  await grid.value?.scrollToTop()
}

const sort = async (field: ArtistListSortField, order: SortOrder) => {
  preferences.albums_sort_field = sortParams.field = field
  preferences.albums_sort_order = sortParams.order = order

  await resetState()
  await nextTick()
  await fetchArtists()
}

useRouter().onScreenActivated('Artists', async () => {
  if (libraryEmpty.value) {
    return
  }
  if (!initialized) {
    viewMode.value = preferences.artists_view_mode || 'thumbnails'
    initialized = true

    try {
      await makeScrollable()
    } catch (error: unknown) {
      initialized = false
      useErrorHandler().handleHttpError(error)
    }
  }
})

watch(viewMode, () => preferences.artists_view_mode = viewMode.value)
</script>
