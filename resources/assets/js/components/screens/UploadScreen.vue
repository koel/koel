<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed"> Upload Media </ScreenHeader>
    </template>

    <div
      v-if="mediaPathSetUp"
      :class="{ droppable }"
      class="relative flex-1 flex flex-col"
      @dragenter.prevent="onDragEnter"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @dragover.prevent
    >
      <DuplicateUploadList v-if="duplicatedSongs.length" :songs="duplicatedSongs" class="mb-4" />

      <div v-if="files.length" class="pb-4 flex flex-col gap-4">
        <UploadSummary class="mb-4" />

        <Tabs class="-mx-6">
          <TabList>
            <TabButton
              v-for="(label, tab) in TAB_LABELS"
              :key="tab"
              :aria-controls="`uploadPane-${tab}`"
              :data-count="filesByTab[tab].length"
              :data-testid="`upload-tab-${tab}`"
              :selected="currentTab === tab"
              @click="currentTab = tab"
            >
              {{ label }}
              <span class="ml-1 rounded-full bg-k-fg-10 px-2 py-0.5 text-[.8rem] tabular-nums">
                {{ filesByTab[tab].length }}
              </span>
            </TabButton>
          </TabList>

          <TabPanelContainer>
            <TabPanel :id="`uploadPane-${currentTab}`" class="flex flex-col gap-4">
              <BtnGroup v-if="currentTab === 'errored' && filesByTab.errored.length" class="self-start" uppercase>
                <Btn variant="success" data-testid="upload-retry-all-btn" @click="retryAll">
                  <Icon :icon="faRotateRight" />
                  Retry All
                </Btn>
                <Btn variant="highlight" data-testid="upload-remove-all-btn" @click="removeFailedEntries">
                  <Icon :icon="faTrashCan" />
                  Remove Failed
                </Btn>
              </BtnGroup>

              <UploadItem
                v-for="file in filesByTab[currentTab]"
                :key="file.id"
                :file="file"
                data-testid="upload-item"
              />
            </TabPanel>
          </TabPanelContainer>
        </Tabs>
      </div>

      <ScreenEmptyState v-else-if="!files.length && !duplicatedSongs.length">
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
import { faRotateRight, faTrashCan, faUpload, faWarning } from '@fortawesome/free-solid-svg-icons'
import { computed, defineAsyncComponent, ref, toRef, onMounted } from 'vue'

import { isDirectoryReadingSupported as canDropFolders } from '@/utils/supports'
import { acceptedExtensions } from '@/utils/mediaHelper'
import { uploadService } from '@/services/uploadService'
import type { UploadFile, UploadStatus } from '@/services/uploadService'
import { useUpload } from '@/composables/useUpload'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ScreenEmptyState from '@/components/ui/ScreenEmptyState.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

import DuplicateUploadList from '@/components/ui/DuplicateUploadList.vue'
import UploadSummary from '@/components/ui/upload/UploadSummary.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))
const UploadItem = defineAsyncComponent(() => import('@/components/ui/upload/UploadItem.vue'))

type UploadTab = 'in-progress' | 'done' | 'skipped' | 'errored'

const TAB_LABELS: Record<UploadTab, string> = {
  'in-progress': 'In Progress',
  done: 'Done',
  skipped: 'Skipped',
  errored: 'Errored',
}

const TAB_STATUSES: Record<UploadTab, UploadStatus[]> = {
  'in-progress': ['Ready', 'Uploading', 'Retrying', 'Processing'],
  done: ['Uploaded'],
  skipped: ['Skipped'],
  errored: ['Errored', 'Canceled'],
}

const acceptAttribute = acceptedExtensions.map(ext => `.${ext}`).join(',')

const { allowsUpload, mediaPathSetUp, queueFilesForUpload, handleDropEvent } = useUpload()

const duplicatedSongs = toRef(uploadService.state, 'duplicatedSongs')

const files = toRef(uploadService.state, 'files')
const currentTab = ref<UploadTab>('in-progress')

const filesByTab = computed(
  () =>
    Object.fromEntries(
      Object.entries(TAB_STATUSES).map(([tab, statuses]) => [
        tab,
        files.value.filter(({ status }) => statuses.includes(status)),
      ]),
    ) as Record<UploadTab, UploadFile[]>,
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
const removeFailedEntries = () => uploadService.removeFailed()

onMounted(() => uploadService.fetchDuplicates())
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.droppable {
  @apply border-2 border-dashed border-white/40 bg-black/20 rounded-3xl;
}
</style>
