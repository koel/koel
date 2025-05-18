<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        <span>Media Browser</span>
        <BreadCrumbs :path class="mt-2" />
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      No files found.
      <span class="secondary block">
        {{ isAdmin ? 'Have you set up your library yet?' : 'Contact your administrator to set up your library.' }}
      </span>
    </ScreenEmptyState>

    <div v-else class="-m-6 h-full min-h-full flex flex-col flex-1 overflow-auto">
      <MediaBrowser :items="items" class="flex-1" @scrolled-to-end="onScrolledToEnd" />
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { useAuthorization } from '@/composables/useAuthorization'
import { useRouter } from '@/composables/useRouter'
import { mediaBrowser } from '@/services/mediaBrowser'
import { useErrorHandler } from '@/composables/useErrorHandler'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import BreadCrumbs from '@/components/song/media-browser/BreadCrumbs.vue'
import MediaBrowser from '@/components/song/media-browser/MediaBrowser.vue'

const { isAdmin } = useAuthorization()
const { onRouteChanged, getRouteParam } = useRouter()

const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const path = ref<string>('')
const getPathFromRoute = () => getRouteParam('path') || ''

const parentFolder = computed(() => mediaBrowser.getParentFolder(path.value))

const subfolders = ref<Folder[]>([])
const songs = ref<Song[]>([])
let nextPage: number | null = 1

const items = computed(() => {
  const _items = [...subfolders.value, ...songs.value]

  if (parentFolder.value) {
    _items.unshift(parentFolder.value)
  }

  return _items
})

const resetState = () => {
  subfolders.value = []
  songs.value = []
  nextPage = 1
}

const loading = ref(false)

const fetchContent = async () => {
  if (loading.value || nextPage === null) {
    return
  }

  try {
    loading.value = true
    const fetched = await mediaBrowser.browse(path.value, nextPage)
    subfolders.value = fetched.subfolders
    songs.value = [...songs.value, ...fetched.songs]
    nextPage = fetched.nextPage
  } catch (e) {
    useErrorHandler().handleHttpError(e)
  } finally {
    loading.value = false
  }
}

const onScrolledToEnd = () => {
  if (nextPage !== null) {
    fetchContent()
  }
}

onRouteChanged(async route => {
  if (route.screen !== 'MediaBrowser') {
    return
  }

  path.value = getPathFromRoute()
  resetState()
  await fetchContent()
})

onMounted(async () => {
  path.value = getPathFromRoute()
  await fetchContent()
})
</script>

<style lang="postcss" scoped></style>
