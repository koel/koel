<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>New Smart Playlist</h1>
    </header>

    <main class="space-y-5">
      <Tabs class="mt-1 -m-6">
        <TabList>
          <TabButton
            id="createSmartPlaylistTabDetails"
            :selected="currentTab === 'details'"
            aria-controls="createSmartPlaylistDetails"
            @click="currentTab = 'details'"
          >
            Details
          </TabButton>
          <TabButton
            id="createSmartPlaylistTabRules"
            :selected="currentTab === 'rules'"
            aria-controls="createSmartPlaylistRules"
            @click="currentTab = 'rules'"
          >
            Rules
          </TabButton>
        </TabList>

        <TabPanelContainer>
          <TabPanel
            v-show="currentTab === 'details'"
            id="createSmartPlaylistDetails"
            aria-labelledby="createSmartPlaylistTabDetails"
            class="space-y-5"
          >
            <div class="grid grid-cols-2 gap-4">
              <FormRow>
                <template #label>Name *</template>
                <TextInput v-model="data.name" v-koel-focus name="name" placeholder="Playlist name" required />
              </FormRow>
              <FormRow>
                <template #label>Folder</template>
                <SelectBox v-model="data.folder_id">
                  <option :value="null" />
                  <option v-for="({ id, name }) in folders" :key="id" :value="id">{{ name }}</option>
                </SelectBox>
              </FormRow>
              <FormRow class="col-span-2">
                <template #label>Description</template>
                <TextArea v-model="data.description" class="h-28" name="description" />
              </FormRow>
              <div class="flex cols-span-2 gap-3 items-center">
                <span v-if="data.cover" class="w-24 h-24 aspect-square relative">
                  <img :src="data.cover" alt="Cover" class="w-24 h-24 rounded object-cover">
                  <button
                    type="button"
                    class="absolute inset-0 opacity-0 hover:opacity-100 bg-black/70 active:bg-black/85 active:text-[.9rem] transition-opacity"
                    @click.prevent="data.cover = null"
                  >
                    Remove
                  </button>
                </span>
                <div class="flex-1">
                  <FileInput v-if="!data.cover" accept="image/*" name="cover" @change="onImageInputChange">
                    Pick a cover (optional)
                  </FileInput>
                </div>
              </div>
            </div>
          </TabPanel>
          <TabPanel
            v-show="currentTab === 'rules'"
            id="createSmartPlaylistRules"
            aria-labelledby="createSmartPlaylistTabRules"
            class="space-y-5"
          >
            <div v-koel-overflow-fade class="group-container space-y-5 overflow-auto max-h-[480px]">
              <RuleGroup
                v-for="(group, index) in collectedRuleGroups"
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
import { isEqual } from 'lodash'
import { ref, toRef } from 'vue'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import type { CreatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useSmartPlaylistForm } from '@/composables/useSmartPlaylistForm'
import { useRouter } from '@/composables/useRouter'
import { useForm } from '@/composables/useForm'
import { useImageFileInput } from '@/composables/useImageFileInput'

import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import FileInput from '@/components/ui/form/FileInput.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const {
  Btn,
  RuleGroup,
  collectedRuleGroups,
  addGroup,
  onGroupChanged,
} = useSmartPlaylistForm()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, url } = useRouter()

const folders = toRef(playlistFolderStore.state, 'folders')
const targetFolder = useModal<'CREATE_SMART_PLAYLIST_FORM'>().getFromContext('folder')
const currentTab = ref<'details' | 'rules'>('details')

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<CreatePlaylistData>({
  initialValues: {
    name: '',
    description: '',
    folder_id: targetFolder?.id || null,
    cover: null,
  },
  isPristine: (original, current) => isEqual(original, current) && collectedRuleGroups.value.length === 0,
  onSubmit: async data => await playlistStore.store({
    ...data,
    rules: collectedRuleGroups.value,
  }),
  onSuccess: (playlist: Playlist) => {
    toastSuccess(`Playlist "${playlist.name}" created.`)
    close()
    go(url('playlists.show', { id: playlist.id }))
  },
})

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
