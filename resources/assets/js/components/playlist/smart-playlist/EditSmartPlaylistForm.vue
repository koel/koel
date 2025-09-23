<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Smart Playlist</h1>
    </header>

    <main class="space-y-5">
      <Tabs class="mt-1 -m-6">
        <TabList>
          <TabButton
            id="createSmartPlaylistTabDetails"
            :selected="isTabActive('details')"
            aria-controls="createSmartPlaylistDetails"
            @click="activateTab('details')"
          >
            Details
          </TabButton>
          <TabButton
            id="createSmartPlaylistTabRules"
            :selected="isTabActive('rules')"
            aria-controls="createSmartPlaylistRules"
            @click="activateTab('rules')"
          >
            Rules
          </TabButton>
        </TabList>

        <TabPanelContainer>
          <TabPanel
            v-show="isTabActive('details')"
            id="createSmartPlaylistDetails"
            aria-labelledby="createSmartPlaylistTabDetails"
            class="space-y-5"
          >
            <div class="grid grid-cols-2 gap-4">
              <FormRow>
                <template #label>Name *</template>
                <TextInput
                  v-model="data.name"
                  v-koel-focus name="name"
                  placeholder="Playlist name"
                  required
                />
              </FormRow>
              <FormRow>
                <template #label>Folder</template>
                <SelectBox v-model="data.folder_id">
                  <option :value="null" />
                  <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
                </SelectBox>
              </FormRow>
              <FormRow class="col-span-2">
                <template #label>Description</template>
                <TextArea v-model="data.description" class="h-28" name="description" />
              </FormRow>
              <div class="flex gap-3 items-center">
                <span v-if="displayedCover" class="w-24 h-24 aspect-square relative">
                  <img :src="displayedCover" alt="Cover" class="w-24 h-24 rounded object-cover">
                  <button
                    type="button"
                    class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
                    @click.prevent="removeOrResetCover"
                  >
                    Remove
                  </button>
                </span>
                <div class="flex-1">
                  <FileInput v-if="!displayedCover" accept="image/*" name="cover" @change="onImageInputChange">
                    Pick a cover (optional)
                  </FileInput>
                </div>
              </div>
            </div>
          </TabPanel>

          <TabPanel
            v-show="isTabActive('rules')"
            id="createSmartPlaylistRules"
            aria-labelledby="createSmartPlaylistTabRules"
            class="space-y-5"
          >
            <div v-koel-overflow-fade class="group-container space-y-5 overflow-auto max-h-[480px]">
              <RuleGroup
                v-for="(group, index) in mutablePlaylist.rules"
                :key="group.id"
                :group="group"
                :is-first-group="index === 0"
                @input="onGroupChanged"
              />
              <Btn class="btn-add-group" small success title="Add a new group" uppercase @click.prevent="addGroup">
                <Icon :icon="faPlus" />
                Group
              </Btn>
            </div>
          </TabPanel>
        </TabPanelContainer>
      </Tabs>
    </main>

    <footer>
      <Btn type="submit">Save</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { computed, reactive, toRef } from 'vue'
import { cloneDeep, isEqual, pick } from 'lodash'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import type { UpdatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { eventBus } from '@/utils/eventBus'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useSmartPlaylistForm } from '@/composables/useSmartPlaylistForm'
import { useForm } from '@/composables/useForm'
import { useImageFileInput } from '@/composables/useImageFileInput'

import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import FileInput from '@/components/ui/form/FileInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const playlist = useModal<'EDIT_SMART_PLAYLIST_FORM'>().getFromContext('playlist')
const folders = toRef(playlistFolderStore.state, 'folders')
const mutablePlaylist = reactive(cloneDeep(playlist))

const {
  Btn,
  RuleGroup,
  activateTab,
  isTabActive,
  collectedRuleGroups,
  addGroup,
  onGroupChanged,
} = useSmartPlaylistForm(cloneDeep(playlist.rules))

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<UpdatePlaylistData>({
  initialValues: pick(playlist, 'name', 'folder_id', 'description'),
  isPristine: (original, current) => isEqual(original, current) && isEqual(collectedRuleGroups.value, playlist.rules),
  onSubmit: async data => await playlistStore.update(playlist, {
    ...data,
    rules: collectedRuleGroups.value,
  }),
  onSuccess: () => {
    toastSuccess(`Playlist "${playlist.name}" updated.`)
    eventBus.emit('PLAYLIST_UPDATED', playlist)
    close()
  },
})

const displayedCover = computed(() => playlist.cover || data.cover)

const removeOrResetCover = async () => {
  if (data.cover) {
    data.cover = null
  } else if (playlist.cover && await showConfirmDialog('Remove the cover? This cannot be undone.')) {
    await playlistStore.removeCover(playlist)
    playlist.cover = null // technically not needed but useful during testing
  }
}

const { onImageInputChange } = useImageFileInput({
  onImageDataUrl: dataUrl => (data.cover = dataUrl),
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>

<style lang="postcss" scoped>
.group-container {
  scrollbar-gutter: stable;
}

form {
  max-height: calc(100vh - 4rem);
}
</style>
