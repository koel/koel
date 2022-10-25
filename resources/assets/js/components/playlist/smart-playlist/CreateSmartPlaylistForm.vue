<template>
  <FormBase>
    <div @keydown.esc="maybeClose">
      <SoundBars v-if="loading"/>
      <form v-else data-testid="create-smart-playlist-form" @submit.prevent="submit">
        <header>
          <h1>New Smart Playlist</h1>
        </header>

        <main>
          <div class="form-row">
            <input v-model="name" v-koel-focus name="name" placeholder="Playlist name" required type="text">
          </div>

          <div class="form-row rules">
            <RuleGroup
              v-for="(group, index) in collectedRuleGroups"
              :key="group.id"
              :group="group"
              :isFirstGroup="index === 0"
              @input="onGroupChanged"
            />
            <Btn class="btn-add-group" green small title="Add a new group" uppercase @click.prevent="addGroup">
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
import { ref } from 'vue'
import { playlistStore } from '@/stores'
import { logger, requireInjection } from '@/utils'
import { DialogBoxKey, MessageToasterKey, RouterKey } from '@/symbols'
import { useSmartPlaylistForm } from '@/components/playlist/smart-playlist/useSmartPlaylistForm'

const {
  Btn,
  FormBase,
  RuleGroup,
  SoundBars,
  collectedRuleGroups,
  loading,
  addGroup,
  onGroupChanged
} = useSmartPlaylistForm()

const toaster = requireInjection(MessageToasterKey)
const dialog = requireInjection(DialogBoxKey)
const router = requireInjection(RouterKey)
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

  try {
    const playlist = await playlistStore.store(name.value, [], collectedRuleGroups.value)
    close()
    toaster.value.success(`Playlist "${playlist.name}" created.`)
    router.go(`playlist/${playlist.id}`)
  } catch (error) {
    dialog.value.error('Something went wrong. Please try again.')
    logger.error(error)
  } finally {
    loading.value = false
  }
}
</script>
