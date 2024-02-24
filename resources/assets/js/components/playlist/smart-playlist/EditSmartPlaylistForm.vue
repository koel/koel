<template>
  <FormBase>
    <form @submit.prevent="submit" @keydown.esc="maybeClose">
      <header>
        <h1>Edit Smart Playlist</h1>
      </header>

      <main>
        <div class="form-row cols">
          <label class="name">
            Name
            <input
              v-model="mutablePlaylist.name"
              v-koel-focus name="name"
              placeholder="Playlist name"
              required
              type="text"
            >
          </label>
          <label class="folder">
            Folder
            <select v-model="mutablePlaylist.folder_id">
              <option :value="null" />
              <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
            </select>
          </label>
        </div>

        <div class="form-row rules" v-koel-overflow-fade>
          <RuleGroup
            v-for="(group, index) in mutablePlaylist.rules"
            :key="group.id"
            :group="group"
            :is-first-group="index === 0"
            @input="onGroupChanged"
          />
          <Btn class="btn-add-group" green small title="Add a new group" uppercase @click.prevent="addGroup">
            <Icon :icon="faPlus" />
          </Btn>
        </div>

        <div class="form-row" v-if="isPlus">
          <label class="own-songs-only text-secondary small">
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
import { eventBus, logger } from '@/utils'
import { useDialogBox, useKoelPlus, useMessageToaster, useModal, useOverlay, useSmartPlaylistForm } from '@/composables'
import CheckBox from '@/components/ui/CheckBox.vue'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()
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
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}
</script>
