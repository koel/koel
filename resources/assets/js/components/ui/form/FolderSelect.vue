<template>
  <div>
    <div
      v-if="creating"
      class="flex items-stretch rounded border border-k-fg-10 overflow-hidden bg-k-bg-input focus-within:border-k-highlight transition-[border] duration-200"
    >
      <input
        ref="newFolderInput"
        v-model="newFolderName"
        aria-label="New folder name"
        class="flex-1 min-w-0 text-base px-3.5 py-2 bg-transparent text-k-fg-input border-0 focus-visible:outline-0"
        placeholder="Folder name"
        type="text"
        @keydown.enter.prevent="submit"
        @keydown.esc.stop.prevent="cancel"
      />
      <button
        :disabled="saving"
        class="px-2.5 bg-k-fg-5 text-k-success hover:text-white border-l border-k-fg-10"
        title="Create"
        type="button"
        @click="submit"
      >
        <Icon :icon="faCheck" fixed-width />
      </button>
      <button
        :disabled="saving"
        class="px-2.5 bg-k-fg-5 text-k-fg-60 hover:text-white border-l border-k-fg-10"
        title="Cancel"
        type="button"
        @click="cancel"
      >
        <Icon :icon="faTimes" fixed-width />
      </button>
    </div>
    <SelectBox v-else v-model="selected" @change="onSelectChange">
      <option :value="null" />
      <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
      <option :value="NEW_FOLDER_SENTINEL">+ New Folder</option>
    </SelectBox>
  </div>
</template>

<script lang="ts" setup>
import { nextTick, ref, toRef, watch } from 'vue'
import { faCheck, faTimes } from '@fortawesome/free-solid-svg-icons'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { useMessageToaster } from '@/composables/useMessageToaster'

import SelectBox from '@/components/ui/form/SelectBox.vue'

const NEW_FOLDER_SENTINEL = '__new__'

const folderId = defineModel<PlaylistFolder['id'] | null | undefined>({ required: true })

const { toastSuccess } = useMessageToaster()

const folders = toRef(playlistFolderStore.state, 'folders')
const creating = ref(false)
const saving = ref(false)
const newFolderName = ref('')
const newFolderInput = ref<HTMLInputElement>()
const selected = ref(folderId.value)

watch(folderId, value => {
  selected.value = value
})

const onSelectChange = () => {
  if (selected.value === NEW_FOLDER_SENTINEL) {
    creating.value = true
    selected.value = folderId.value
    nextTick(() => newFolderInput.value?.focus())
  } else {
    folderId.value = selected.value
  }
}

const submit = async () => {
  const name = newFolderName.value.trim()

  if (!name) {
    return
  }

  saving.value = true

  try {
    const folder = await playlistFolderStore.store(name)
    folderId.value = folder.id
    toastSuccess(`Folder "${folder.name}" created.`)
    reset()
  } finally {
    saving.value = false
  }
}

const cancel = () => {
  reset()
  selected.value = folderId.value
}

const maybeCreateFolder = async () => {
  if (creating.value && newFolderName.value.trim()) {
    await submit()
  }
}

const reset = () => {
  creating.value = false
  newFolderName.value = ''
}

defineExpose({ maybeCreateFolder })
</script>
