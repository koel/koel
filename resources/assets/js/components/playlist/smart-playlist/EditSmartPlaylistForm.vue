<template>
  <FormBase>
    <form @submit.prevent="submit" @keydown.esc="maybeClose">
      <header>
        <h1>Edit Smart Playlist</h1>
      </header>

      <main>
        <div class="form-row">
          <input
            v-model="mutablePlaylist.name"
            v-koel-focus name="name"
            placeholder="Playlist name"
            required
            type="text"
          >
        </div>

        <div class="form-row rules">
          <RuleGroup
            v-for="(group, index) in mutablePlaylist.rules"
            :key="group.id"
            :group="group"
            :isFirstGroup="index === 0"
            @input="onGroupChanged"
          />
          <Btn class="btn-add-group" green small title="Add a new group" uppercase @click.prevent="addGroup">
            <icon :icon="faPlus"/>
          </Btn>
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
import { computed, reactive, watch } from 'vue'
import { cloneDeep, isEqual } from 'lodash'
import { playlistStore } from '@/stores'
import { eventBus, logger, requireInjection } from '@/utils'
import { useDialogBox, useMessageToaster, useOverlay, useSmartPlaylistForm } from '@/composables'
import { PlaylistKey } from '@/symbols'

const { showOverlay, hideOverlay } = useOverlay()
const { toastSuccess } = useMessageToaster()
const { showConfirmDialog, showErrorDialog } = useDialogBox()

const [playlist] = requireInjection(PlaylistKey)

let mutablePlaylist: Playlist

watch(playlist, () => (mutablePlaylist = reactive(cloneDeep(playlist.value))), { immediate: true })

const isPristine = computed(() => {
  return isEqual(mutablePlaylist.rules, playlist.value.rules) && mutablePlaylist.name.trim() === playlist.value.name
})

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
  if (isPristine.value) {
    close()
    return
  }

  await showConfirmDialog('Discard all changes?') && close()
}

const submit = async () => {
  showOverlay()

  mutablePlaylist.rules = collectedRuleGroups.value

  try {
    await playlistStore.update(playlist.value, {
      name: mutablePlaylist.name,
      rules: mutablePlaylist.rules
    })

    toastSuccess(`Playlist "${playlist.value.name}" updated.`)
    eventBus.emit('PLAYLIST_UPDATED', playlist.value)
    close()
  } catch (error) {
    showErrorDialog('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    hideOverlay()
  }
}
</script>
