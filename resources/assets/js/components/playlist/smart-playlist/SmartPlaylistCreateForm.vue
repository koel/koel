<template>
  <FormBase>
    <div @keydown.esc="maybeClose">
      <SoundBar v-if="loading"/>
      <form v-else data-testid="create-smart-playlist-form" @submit.prevent="submit">
        <header>
          <h1>New Smart Playlist</h1>
        </header>

        <main>
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
              <icon :icon="faPlus"/>
              Group
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
import { nextTick, ref } from 'vue'
import { playlistStore } from '@/stores'
import { requireInjection } from '@/utils'
import { DialogBoxKey, MessageToasterKey } from '@/symbols'
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

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)
const name = ref('')

const emit = defineEmits(['close'])
const close = () => emit('close')

const maybeClose = async () => {
  if (!name.value && !collectedRuleGroups.value.length) {
    close()
    return
  }

  await dialog.value.confirm('Discard all changes?') && close()
}

const submit = async () => {
  loading.value = true
  const playlist = await playlistStore.store(name.value, [], collectedRuleGroups.value)
  loading.value = false
  close()

  toaster.value.success(`Playlist "${playlist.name}" created.`)

  await nextTick()
  router.go(`playlist/${playlist.id}`)
}
</script>
