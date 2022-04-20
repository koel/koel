<template>
  <FormBase>
    <div @keydown.esc="maybeClose">
      <SoundBar v-if="loading"/>
      <form @submit.prevent="submit" v-else data-testid="create-smart-playlist-form">
        <header>
          <h1>New Smart Playlist</h1>
        </header>

        <div>
          <div class="form-row">
            <label>Name</label>
            <input type="text" v-model="name" name="name" v-koel-focus required>
          </div>

          <div class="form-row rules">
            <RuleGroup
              :group="group"
              :isFirstGroup="index === 0"
              :key="group.id"
              @input="onGroupChanged"
              v-for="(group, index) in collectedRuleGroups"
            />
            <Btn @click.prevent="addGroup" class="btn-add-group" green small uppercase>
              <i class="fa fa-plus"></i> Group
            </Btn>
          </div>
        </div>

        <footer>
          <Btn type="submit">Save</Btn>
          <Btn class="btn-cancel" @click.prevent="maybeClose" white>Cancel</Btn>
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
  await nextTick()
  router.go(`playlist/${playlist.id}`)
}
</script>
