<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed"> Upload Media </ScreenHeader>
    </template>

    <div
      v-if="mediaPathSetUp"
      :class="{ droppable }"
      class="relative flex-1 min-h-0 flex flex-col"
      @dragenter.prevent="onDragEnter"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @dragover.prevent
    >
      <div v-if="showsFilters" :class="{ 'flex-1': !showsDropPrompt }" class="min-h-0 flex flex-col gap-4">
        <UploadSummary v-if="files.length" class="mb-4" />

        <SegmentedControl v-model="currentFilter" :options="filterOptions" class="self-center" name="upload-filter">
          <template #default="{ option }">
            {{ option.label }}
            <span
              :data-count="filterCounts[option.value]"
              :data-filter="option.value"
              :data-testid="`upload-filter-count-${option.value}`"
              class="count inline-flex items-center justify-center h-[16px] min-w-[16px] px-[5px] rounded-full bg-k-fg-10 text-[.8rem] leading-none tabular-nums"
              data-badge
            >
              {{ filterCounts[option.value] }}
            </span>
          </template>
        </SegmentedControl>

        <div class="flex-1 min-h-0 flex flex-col gap-4">
          <DuplicateUploadList v-if="currentFilter === 'duplicated'" :songs="duplicatedSongs" class="flex-1 min-h-0" />

          <VirtualScroller
            v-else-if="filesByFilter[currentFilter].length"
            :item-height="ROW_HEIGHT"
            :items="filesByFilter[currentFilter]"
            class="flex-1 -mr-6 pr-6"
          >
            <template #default="{ item }: { item: UploadFile }">
              <div :key="item.id" :style="{ height: `${ROW_HEIGHT}px`, paddingBottom: `${ROW_GAP}px` }">
                <UploadItem :file="item" class="h-full" data-testid="upload-item" />
              </div>
            </template>
          </VirtualScroller>

          <footer v-if="currentFilter === 'errored' && filesByFilter.errored.length" class="flex justify-end gap-2">
            <Btn variant="success" data-testid="upload-retry-all-btn" @click="retryAll">Retry All</Btn>
            <Btn variant="destructive" data-testid="upload-remove-all-btn" @click="removeFailedEntries">
              Remove Failed
            </Btn>
          </footer>
        </div>
      </div>

      <ScreenEmptyState v-if="showsDropPrompt" data-testid="upload-drop-prompt">
        <template #icon>
          <Icon :icon="faUpload" />
        </template>

        {{ canDropFolders ? 'Drop files or folders to upload' : 'Drop files to upload' }}

        <span class="secondary block">
          <a class="block relative text-k-fg-70! hover:text-k-fg!" role="button">
            or click here to select songs
            <input
              :accept="acceptAttribute"
              class="absolute opacity-0 w-full h-full z-2 cursor-pointer left-0 top-0"
              multiple
              name="file[]"
              type="file"
              @change="onFileInputChange"
            />
          </a>
        </span>
      </ScreenEmptyState>
    </div>

    <ScreenEmptyState v-else>
      <template #icon>
        <Icon :icon="faWarning" />
      </template>
      No media path set.
    </ScreenEmptyState>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faUpload, faWarning } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, ref, toRef, onMounted, watch } from 'vue'

import { isDirectoryReadingSupported as canDropFolders } from '@/utils/supports'
import { acceptedExtensions } from '@/utils/mediaHelper'
import { uploadService } from '@/services/uploadService'
import type { UploadFile, UploadStatus } from '@/services/uploadService'
import { useUpload } from '@/composables/useUpload'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

import DuplicateUploadList from '@/components/ui/DuplicateUploadList.vue'
import UploadSummary from '@/components/ui/upload/UploadSummary.vue'
import SegmentedControl from '@/components/ui/SegmentedControl.vue'
import VirtualScroller from '@/components/ui/VirtualScroller.vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))
const UploadItem = defineAsyncComponent(() => import('@/components/ui/upload/UploadItem.vue'))

type FileFilter = 'in-progress' | 'done' | 'skipped' | 'errored'
type UploadFilter = FileFilter | 'duplicated'

const FILTER_LABELS: Record<UploadFilter, string> = {
  'in-progress': 'In Progress',
  done: 'Done',
  skipped: 'Skipped',
  errored: 'Errored',
  duplicated: 'Duplicated',
}

const FILTER_STATUSES: Record<FileFilter, UploadStatus[]> = {
  'in-progress': ['Ready', 'Uploading', 'Retrying', 'Processing'],
  done: ['Uploaded'],
  skipped: ['Skipped'],
  errored: ['Errored', 'Canceled'],
}

const ROW_GAP = 6
const ROW_HEIGHT = 36 + ROW_GAP

const acceptAttribute = acceptedExtensions.map(ext => `.${ext}`).join(',')

const { allowsUpload, mediaPathSetUp, queueFilesForUpload, handleDropEvent } = useUpload()

const duplicatedSongs = toRef(uploadService.state, 'duplicatedSongs')

const files = toRef(uploadService.state, 'files')
const currentFilter = ref<UploadFilter>('in-progress')

const filesByFilter = computed(
  () =>
    Object.fromEntries(
      Object.entries(FILTER_STATUSES).map(([filter, statuses]) => [
        filter,
        files.value.filter(({ status }) => statuses.includes(status)),
      ]),
    ) as Record<FileFilter, UploadFile[]>,
)
const filterOptions = computed(() =>
  (Object.keys(FILTER_LABELS) as UploadFilter[]).map(filter => ({
    value: filter,
    label: FILTER_LABELS[filter],
    testId: `upload-filter-${filter}`,
  })),
)
const filterCounts = computed<Record<UploadFilter, number>>(() => ({
  'in-progress': filesByFilter.value['in-progress'].length,
  done: filesByFilter.value.done.length,
  skipped: filesByFilter.value.skipped.length,
  errored: filesByFilter.value.errored.length,
  duplicated: duplicatedSongs.value.length,
}))

const showsFilters = computed(() => files.value.length > 0 || duplicatedSongs.value.length > 0)
const showsDropPrompt = computed(
  () => !showsFilters.value || (currentFilter.value === 'in-progress' && !filesByFilter.value['in-progress'].length),
)

const droppable = ref(false)

const onDragEnter = () => (droppable.value = allowsUpload.value)

const onDragLeave = (e: MouseEvent) => {
  if ((e.currentTarget as Node)?.contains?.(e.relatedTarget as Node)) {
    return
  }

  droppable.value = false
}

const onFileInputChange = (event: Event) => {
  const selectedFileList = (event.target as HTMLInputElement).files

  if (selectedFileList?.length) {
    queueFilesForUpload(Array.from(selectedFileList))
  }
}

const onDrop = async (event: DragEvent) => {
  droppable.value = false
  await handleDropEvent(event)
}

const retryAll = () => uploadService.retryAll()

watch(
  () => filesByFilter.value.errored.length,
  erroredCount => {
    if (!erroredCount && currentFilter.value === 'errored') {
      currentFilter.value = 'in-progress'
    }
  },
)
const removeFailedEntries = () => uploadService.removeFailed()

onMounted(async () => {
  await uploadService.fetchDuplicates()

  if (!files.value.length && duplicatedSongs.value.length) {
    currentFilter.value = 'duplicated'
  }
})
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.droppable {
  @apply border-2 border-dashed border-white/40 bg-black/20 rounded-3xl;
}

.count {
  &[data-filter='in-progress'] {
    @apply bg-k-primary text-white;
  }

  &[data-filter='done'] {
    @apply bg-k-success text-white;
  }

  &[data-filter='errored'] {
    @apply bg-k-danger text-white;
  }

  &[data-filter='duplicated'] {
    @apply bg-k-warning text-white;
  }
}
</style>
