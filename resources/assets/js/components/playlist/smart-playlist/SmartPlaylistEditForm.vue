<template>
  <FormBase>
    <div @keydown.esc="maybeClose">
      <SoundBar v-if="loading"/>
      <form @submit.prevent="submit" v-else data-testid="edit-smart-playlist-form">
        <header>
          <h1>Edit Smart Playlist</h1>
        </header>

        <div>
          <div class="form-row">
            <label>Name</label>
            <input type="text" v-model="mutatedPlaylist.name" name="name" v-koel-focus required>
          </div>

          <div class="form-row rules">
            <RuleGroup
              v-for="(group, index) in mutatedPlaylist.rules"
              :isFirstGroup="index === 0"
              :key="group.id"
              :group="group"
              @input="onGroupChanged"
            />
            <Btn @click.prevent="addGroup" class="btn-add-group" green small uppercase>
              <i class="fa fa-plus"></i> Group
            </Btn>
          </div>
        </div>

        <footer>
          <Btn type="submit">Save</Btn>
          <Btn white class="btn-cancel" @click.prevent="maybeClose">Cancel</Btn>
        </footer>
      </form>
    </div>
  </FormBase>
</template>

<script lang="ts" setup>
import { reactive, toRefs } from 'vue'
import { cloneDeep, isEqual } from 'lodash'
import { playlistStore } from '@/stores'
import { alerts, eventBus } from '@/utils'
import { useSmartPlaylistForm } from '@/components/playlist/smart-playlist/useSmartPlaylistForm'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const mutatedPlaylist = reactive<Playlist>(cloneDeep(playlist.value))

const {
  Btn,
  FormBase,
  RuleGroup,
  SoundBar,
  collectedRuleGroups,
  loading,
  addGroup,
  onGroupChanged
} = useSmartPlaylistForm(mutatedPlaylist.rules)

const emit = defineEmits(['close'])
const close = () => emit('close')

const maybeClose = () => {
  if (isEqual(playlist.value, mutatedPlaylist)) {
    close()
    return
  }

  alerts.confirm('Discard all changes?', close)
}

const submit = async () => {
  loading.value = true
  mutatedPlaylist.rules = collectedRuleGroups.value
  await playlistStore.update(mutatedPlaylist)
  Object.assign(playlist.value, mutatedPlaylist)
  loading.value = false
  eventBus.emit('SMART_PLAYLIST_UPDATED', playlist.value)
  close()
}
</script>
