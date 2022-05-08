<template>
  <FormBase>
    <div @keydown.esc="maybeClose">
      <SoundBar v-if="loading"/>
      <form v-else data-testid="create-smart-playlist-form" @submit.prevent="submit">
        <header>
          <h1>New Smart Playlist</h1>
        </header>

        <div>
          <div class="form-row">
            <label>Name</label>
            <input v-model="name" v-koel-focus name="name" required type="text">
          </div>

          <div class="form-row rules">
            <RuleGroup
              v-for="(group, index) in collectedRuleGroups"
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
import { nextTick, ref } from 'vue'
import { playlistStore } from '@/stores'
import { alerts } from '@/utils'
import router from '@/router'
import { useSmartPlaylistForm } from '@/components/playlist/smart-playlist/useSmartPlaylistForm'

const {
  Btn,
  FormBase,
  RuleGroup,
  SoundBar,
  collectedRuleGroups,
  loading,
  addGroup,
  onGroupChanged
} = useSmartPlaylistForm()

const name = ref('')

const emit = defineEmits(['close'])
const close = () => emit('close')

const maybeClose = () => {
  if (!name.value && !collectedRuleGroups.value.length) {
    close()
    return
  }

  alerts.confirm('Discard all changes?', close)
}

const submit = async () => {
  loading.value = true
  const playlist = await playlistStore.store(name.value, [], collectedRuleGroups.value)
  loading.value = false
  close()

  alerts.success(`Playlist "${playlist.name}" created.`)

  await nextTick()
  router.go(`playlist/${playlist.id}`)
}
</script>
