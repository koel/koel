<template>
  <FormBase>
    <form @submit.prevent="submit" @keydown.esc="maybeClose">
      <header>
        <h1>New Smart Playlist</h1>
      </header>

      <main class="space-y-5">
        <FormRow :cols="2">
          <FormRow>
            <template #label>Name</template>
            <TextInput v-model="name" v-koel-focus name="name" placeholder="Playlist name" required />
          </FormRow>
          <FormRow>
            <template #label>Folder</template>
            <SelectBox v-model="folderId">
              <option :value="null" />
              <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
            </SelectBox>
          </FormRow>
        </FormRow>

        <div v-koel-overflow-fade class="group-container space-y-5 overflow-auto max-h-[480px]">
          <RuleGroup
            v-for="(group, index) in collectedRuleGroups"
            :key="group.id"
            :group="group"
            :is-first-group="index === 0"
            @input="onGroupChanged"
          />
          <Btn class="btn-add-group" success small title="Add a new group" uppercase @click.prevent="addGroup">
            <Icon :icon="faPlus" />
            Group
          </Btn>
        </div>

        <div v-if="isPlus" class="form-row">
          <label class="text-k-text-secondary">
            <CheckBox v-model="ownSongsOnly" />
            Only include songs from my own library
          </label>
        </div>
      </main>

      <footer>
        <Btn type="submit">Save</Btn>
        <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
      </footer>
    </form>
  </FormBase>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { ref, toRef } from 'vue'
import { playlistFolderStore, playlistStore } from '@/stores'
import { logger } from '@/utils'
import {
  useDialogBox,
  useKoelPlus,
  useMessageToaster,
  useModal,
  useOverlay,
  useRouter,
  useSmartPlaylistForm
} from '@/composables'

import CheckBox from '@/components/ui/form/CheckBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const {
  Btn,
  FormBase,
  RuleGroup,
  collectedRuleGroups,
  addGroup,
  onGroupChanged
} = useSmartPlaylistForm()

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
const { go } = useRouter()
const { isPlus } = useKoelPlus()

const targetFolder = useModal().getFromContext<PlaylistFolder | null>('folder')

const name = ref('')
const folderId = ref(targetFolder?.id)
const folders = toRef(playlistFolderStore.state, 'folders')
const ownSongsOnly = ref(false)

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const isPristine = () => name.value === ''
  && folderId.value === targetFolder?.id
  && collectedRuleGroups.value.length === 0

const maybeClose = async () => {
  if (isPristine()) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}

const submit = async () => {
  showOverlay()

  try {
    const playlist = await playlistStore.store(name.value, {
      rules: collectedRuleGroups.value,
      folder_id: folderId.value,
      own_songs_only: ownSongsOnly.value
    })

    close()
    toastSuccess(`Playlist "${playlist.name}" created.`)
    go(`playlist/${playlist.id}`)
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}
</script>

<style lang="postcss" scoped>
.group-container {
  scrollbar-gutter: stable;
}
</style>
