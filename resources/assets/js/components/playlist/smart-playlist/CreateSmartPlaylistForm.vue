<template>
  <FormBase>
    <form @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
      <header>
        <h1>New Smart Playlist</h1>
      </header>

      <main class="space-y-5">
        <FormRow :cols="2">
          <FormRow>
            <template #label>Name</template>
            <TextInput v-model="data.name" v-koel-focus name="name" placeholder="Playlist name" required />
          </FormRow>
          <FormRow>
            <template #label>Folder</template>
            <SelectBox v-model="data.folder_id">
              <option :value="null" />
              <option v-for="({ id, name }) in folders" :key="id" :value="id">{{ name }}</option>
            </SelectBox>
          </FormRow>
        </FormRow>

        <div v-koel-overflow-fade class="group-container space-y-5 overflow-auto max-h-[480px]">
          <RuleGroup
            v-for="(group, index) in collectedRuleGroups"
            :key="group.id"
            :group="group"
            :is-first-group="index === 0"
            @input="onGroupChanged"
          />
          <Btn class="btn-add-group" small success title="Add a new group" uppercase @click.prevent="addGroup">
            <Icon :icon="faPlus" />
            Group
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
import { isEqual } from 'lodash'
import { toRef } from 'vue'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { useSmartPlaylistForm } from '@/composables/useSmartPlaylistForm'
import { useRouter } from '@/composables/useRouter'
import { useForm } from '@/composables/useForm'

import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'

const emit = defineEmits<{ (e: 'close'): void }>()

const {
  Btn,
  FormBase,
  RuleGroup,
  collectedRuleGroups,
  addGroup,
  onGroupChanged,
} = useSmartPlaylistForm()

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()
const { go, url } = useRouter()

const folders = toRef(playlistFolderStore.state, 'folders')
const targetFolder = useModal().getFromContext<PlaylistFolder | null>('folder')

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<{
  name: Playlist['name']
  folder_id: PlaylistFolder['id'] | null
}>({
  initialValues: {
    name: '',
    folder_id: targetFolder?.id || null,
  },
  isPristine: (original, current) => isEqual(original, current) && collectedRuleGroups.value.length === 0,
  onSubmit: async ({ name, folder_id }) => await playlistStore.store(name, {
    folder_id,
    rules: collectedRuleGroups.value,
  }),
  onSuccess: (playlist: Playlist) => {
    toastSuccess(`Playlist "${playlist.name}" created.`)
    close()
    go(url('playlists.show', { id: playlist.id }))
  },
})

const maybeClose = async () => {
  if (isPristine() || await showConfirmDialog('Discard all changes?')) {
    close()
  }
}
</script>

<style lang="postcss" scoped>
.group-container {
  scrollbar-gutter: stable;
}
</style>
