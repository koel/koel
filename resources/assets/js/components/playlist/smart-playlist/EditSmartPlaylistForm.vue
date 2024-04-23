<template>
  <FormBase>
    <form @submit.prevent="submit" @keydown.esc="maybeClose">
      <header>
        <h1>Edit Smart Playlist</h1>
      </header>

      <main class="space-y-5">
        <FormRow :cols="2">
          <FormRow>
            <template #label>Name</template>
            <TextInput
              v-model="mutablePlaylist.name"
              v-koel-focus name="name"
              placeholder="Playlist name"
              required
            />
          </FormRow>
          <FormRow>
            <template #label>Folder</template>
            <SelectBox v-model="mutablePlaylist.folder_id">
              <option :value="null" />
              <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
            </SelectBox>
          </FormRow>
        </FormRow>

        <div v-koel-overflow-fade class="group-container space-y-5 overflow-auto max-h-[480px]">
          <RuleGroup
            v-for="(group, index) in mutablePlaylist.rules"
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
            <CheckBox v-model="mutablePlaylist.own_songs_only" /> Only include songs from my own library
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
import { reactive, toRef } from 'vue'
import { cloneDeep, isEqual } from 'lodash'
import { playlistFolderStore, playlistStore } from '@/stores'
import { eventBus } from '@/utils'
import {
  useDialogBox,
  useErrorHandler,
  useKoelPlus,
  useMessageToaster,
  useModal,
  useOverlay,
  useSmartPlaylistForm
} from '@/composables'
import CheckBox from '@/components/ui/form/CheckBox.vue'
import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const {isPlus} = useKoelPlus()

const playlist = useModal().getFromContext<Playlist>('playlist')
const folders = toRef(playlistFolderStore.state, 'folders')
const mutablePlaylist = reactive(cloneDeep(playlist))

const isPristine = () => isEqual(mutablePlaylist.rules, playlist.rules)
  && mutablePlaylist.name.trim() === playlist.name
  && mutablePlaylist.folder_id === playlist.folder_id

const {
  Btn,
  FormBase,
  RuleGroup,
  collectedRuleGroups,
  addGroup,
  onGroupChanged
} = useSmartPlaylistForm(mutablePlaylist.rules)

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const maybeClose = async () => {
  if (isPristine()) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}

const submit = async () => {
  showOverlay()

  mutablePlaylist.rules = collectedRuleGroups.value

  try {
    await playlistStore.update(playlist, mutablePlaylist)
    toastSuccess(`Playlist "${playlist.name}" updated.`)
    eventBus.emit('PLAYLIST_UPDATED', playlist)
    close()
  } catch (error: unknown) {
    useErrorHandler('dialog').handleHttpError(error)
  } finally {
    hideOverlay()
  }
}
</script>
