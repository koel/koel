<template>
  <div>
    <div
      v-if="entering"
      class="flex items-stretch rounded border border-k-fg-10 overflow-hidden bg-k-bg-input focus-within:border-k-highlight transition-[border] duration-200"
    >
      <input
        ref="newFolderInput"
        v-model="inputName"
        aria-label="New folder name"
        class="flex-1 min-w-0 text-base px-3.5 py-2 bg-transparent text-k-fg-input border-0 focus-visible:outline-hidden"
        placeholder="Folder name"
        type="text"
        @keydown.enter.prevent="confirm"
        @keydown.esc.stop.prevent="cancel"
      />
      <button
        class="px-2.5 bg-k-fg-5 text-k-success hover:text-white border-l border-k-fg-10"
        title="Create"
        type="button"
        @click="confirm"
      >
        <Icon :icon="faCheck" fixed-width />
      </button>
      <button
        class="px-2.5 bg-k-fg-5 text-k-fg-60 hover:text-white border-l border-k-fg-10"
        title="Cancel"
        type="button"
        @click="cancel"
      >
        <Icon :icon="faTimes" fixed-width />
      </button>
    </div>
    <SelectBox v-else v-model="selected" @update:model-value="onSelectChange">
      <option :value="null" />
      <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
      <option v-if="folderName" :value="PENDING_FOLDER">{{ folderName }} (new)</option>
      <option :value="NEW_FOLDER">+ New Folder</option>
    </SelectBox>
  </div>
</template>

<script lang="ts" setup>
import { nextTick, ref, toRef, watch } from 'vue'
import { faCheck, faTimes } from '@fortawesome/free-solid-svg-icons'
import { playlistFolderStore } from '@/stores/playlistFolderStore'

import SelectBox from '@/components/ui/form/SelectBox.vue'

const NEW_FOLDER = '__new__'
const PENDING_FOLDER = '__pending__'

const folderId = defineModel<PlaylistFolder['id'] | null | undefined>('folderId', { required: true })
const folderName = defineModel<string | null>('folderName', { default: null })

const folders = toRef(playlistFolderStore.state, 'folders')
const entering = ref(false)
const inputName = ref('')
const newFolderInput = ref<HTMLInputElement>()
const selected = ref(folderName.value ? PENDING_FOLDER : folderId.value)

watch([folderId, folderName], ([id, name]) => {
  selected.value = name ? PENDING_FOLDER : id
})

const onSelectChange = () => {
  if (selected.value === NEW_FOLDER || selected.value === PENDING_FOLDER) {
    entering.value = true
    inputName.value = folderName.value ?? ''
    nextTick(() => newFolderInput.value?.focus())
  } else {
    folderId.value = selected.value
    folderName.value = null
  }
}

const confirm = () => {
  const name = inputName.value.trim()

  if (!name) {
    return
  }

  folderName.value = name
  folderId.value = null
  selected.value = PENDING_FOLDER
  entering.value = false
}

const cancel = () => {
  entering.value = false
  inputName.value = ''
  selected.value = folderName.value ? PENDING_FOLDER : folderId.value
}
</script>
