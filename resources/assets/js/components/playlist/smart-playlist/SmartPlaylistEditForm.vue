<template>
  <FormBase>
    <div @keydown.esc="maybeClose">
      <SoundBar v-if="loading"/>
      <form v-else data-testid="edit-smart-playlist-form" @submit.prevent="submit">
        <header>
          <h1>Edit Smart Playlist</h1>
        </header>

        <div>
          <div class="form-row">
            <label>Name</label>
            <input v-model="mutatedPlaylist.name" v-koel-focus name="name" required type="text">
          </div>

          <div class="form-row rules">
            <RuleGroup
              v-for="(group, index) in mutatedPlaylist.rules"
              :key="group.id"
              :group="group"
              :isFirstGroup="index === 0"
              @input="onGroupChanged"
            />
            <Btn class="btn-add-group" green small uppercase @click.prevent="addGroup">
              <i class="fa fa-plus"></i> Group
            </Btn>
          </div>
        </div>

        <footer>
          <Btn type="submit">Save</Btn>
          <Btn class="btn-cancel" white @click.prevent="maybeClose">Cancel</Btn>
        </footer>
      </form>
    </div>
  </FormBase>
</template>

<script lang="ts" setup>
import { computed, reactive, toRefs } from 'vue'
import { cloneDeep, isEqual } from 'lodash'
import { playlistStore } from '@/stores'
import { alerts, eventBus } from '@/utils'
import { useSmartPlaylistForm } from '@/components/playlist/smart-playlist/useSmartPlaylistForm'

const props = defineProps<{ playlist: Playlist }>()
const { playlist } = toRefs(props)

const mutatedPlaylist = reactive<Playlist>(cloneDeep(playlist.value))

const isPristine = computed(() => {
  return isEqual(mutatedPlaylist.rules, playlist.value.rules) && mutatedPlaylist.name.trim() === playlist.value.name
})

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
  if (isPristine.value) {
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
  alerts.success(`Playlist "${playlist.value.name}" updated.`)
  eventBus.emit('SMART_PLAYLIST_UPDATED', playlist.value)
  close()
}
</script>
