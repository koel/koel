<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader :disabled="loading">
        Media Browser

        <template #meta>
          <div class="flex items-center gap-2 mt-2">
            <Breadcrumbs :path class="flex-1" />
            <Btn transparent small title="Reload" @click.prevent="refresh">
              <Icon :icon="faRotateRight" />
            </Btn>
          </div>
        </template>
      </ScreenHeader>
    </template>

    <ScreenEmptyState v-if="libraryEmpty">
      No files found.
      <span v-if="currentUserCan.manageSettings()" class="secondary block">
        Have you set up your library yet?
      </span>
    </ScreenEmptyState>

    <div v-else class="-m-6 h-full min-h-full flex flex-col flex-1 overflow-auto">
      <MediaListView
        v-show="!shouldShowSkeleton"
        :items
        :path
        :class="noContent || 'flex-1'"
        @scrolled-to-end="onScrolledToEnd"
      />

      <MediaListViewSkeleton v-if="shouldShowSkeleton" />

      <ScreenEmptyState v-if="noContent">
        <template #icon>
          <Icon :icon="faFolderOpen" class="text-k-text-primary" />
        </template>
        This folder is empty.
      </ScreenEmptyState>
    </div>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faRotateRight } from '@fortawesome/free-solid-svg-icons'
import { faFolderOpen } from '@fortawesome/free-regular-svg-icons'
import { pull } from 'lodash'
import { computed, ref } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { useRouter } from '@/composables/useRouter'
import { mediaBrowser } from '@/services/mediaBrowser'
import { useErrorHandler } from '@/composables/useErrorHandler'
import { eventBus } from '@/utils/eventBus'
import { usePolicies } from '@/composables/usePolicies'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'
import Breadcrumbs from '@/components/playable/media-browser/Breadcrumbs.vue'
import MediaListView from '@/components/playable/media-browser/MediaListView.vue'
import MediaListViewSkeleton from '@/components/playable/media-browser/MediaListViewSkeleton.vue'
import Btn from '@/components/ui/form/Btn.vue'

const { currentUserCan } = usePolicies()
const { onRouteChanged, getRouteParam, onScreenActivated } = useRouter()

const libraryEmpty = computed(() => commonStore.state.song_length === 0)

const loading = ref(false)
const path = ref<string>('')

const getPathFromRoute = () => getRouteParam('path') || ''

const subfolders = ref<Folder[]>([])
const songs = ref<Song[]>([])
const nextPage = ref<number | null>(1)

const parentFolder = computed(() => mediaBrowser.getParentFolder(path.value))
const noContent = computed(() => !loading.value && !subfolders.value.length && !songs.value.length)

const shouldShowSkeleton = computed(() => {
  return loading.value && nextPage.value === 1
})

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
  nextPage.value = 1
}

const fetchContent = async (forceRefresh = false) => {
  if (loading.value || nextPage.value === null) {
    return
  }

  try {
    loading.value = true
    const fetched = await mediaBrowser.browse(path.value, nextPage.value, forceRefresh)
    subfolders.value = fetched.subfolders
    songs.value = [...songs.value, ...fetched.songs]
    nextPage.value = fetched.nextPage
  } catch (e) {
    useErrorHandler().handleHttpError(e)
  } finally {
    loading.value = false
  }
}

const refresh = () => {
  resetState()
  fetchContent(true)
}

const onScrolledToEnd = () => {
  if (nextPage.value !== null) {
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

eventBus.on('SONGS_DELETED', async deletedSongs => {
  pull(songs.value, ...deletedSongs)

  if (!songs.value.length) {
    resetState()
    await fetchContent()
  }
})

onScreenActivated('MediaBrowser', async () => {
  path.value = getPathFromRoute()
  await fetchContent()
})
</script>

<style lang="postcss" scoped></style>
