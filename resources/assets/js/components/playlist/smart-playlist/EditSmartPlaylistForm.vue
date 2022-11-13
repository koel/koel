<template>
  <FormBase>
    <div @keydown.esc="maybeClose">
      <SoundBars v-if="loading"/>
      <form v-else data-testid="edit-smart-playlist-form" @submit.prevent="submit">
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
    </div>
  </FormBase>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { computed, reactive, watch } from 'vue'
import { cloneDeep, isEqual } from 'lodash'
import { playlistStore } from '@/stores'
import { eventBus, logger, requireInjection } from '@/utils'
import { useSmartPlaylistForm } from '@/components/playlist/smart-playlist/useSmartPlaylistForm'
import { DialogBoxKey, MessageToasterKey, PlaylistKey } from '@/symbols'

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)
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
  SoundBars,
  collectedRuleGroups,
  loading,
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

  await dialog.value.confirm('Discard all changes?') && close()
}

const submit = async () => {
  loading.value = true
  mutablePlaylist.rules = collectedRuleGroups.value

  try {
    await playlistStore.update(playlist.value, {
      name: mutablePlaylist.name,
      rules: mutablePlaylist.rules
    })

    toaster.value.success(`Playlist "${playlist.value.name}" updated.`)
    eventBus.emit('PLAYLIST_UPDATED', playlist.value)
    close()
  } catch (error) {
    dialog.value.error('Something went wrong. Please try again.', 'Error')
    logger.error(error)
  } finally {
    loading.value = false
  }
}
</script>
