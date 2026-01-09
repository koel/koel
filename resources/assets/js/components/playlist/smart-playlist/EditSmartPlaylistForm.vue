<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>{{ t('smartPlaylists.edit') }}</h1>
    </header>

    <main class="space-y-5">
      <Tabs class="mt-1 -m-6">
        <TabList>
          <TabButton
            id="createSmartPlaylistTabDetails"
            :selected="isTabActive('details')"
            aria-controls="createSmartPlaylistDetails"
            @click="activateTab('details')"
          >
            {{ t('smartPlaylists.details') }}
          </TabButton>
          <TabButton
            id="createSmartPlaylistTabRules"
            :selected="isTabActive('rules')"
            aria-controls="createSmartPlaylistRules"
            @click="activateTab('rules')"
          >
            {{ t('smartPlaylists.rules') }}
          </TabButton>
        </TabList>

        <TabPanelContainer>
          <TabPanel
            v-show="isTabActive('details')"
            id="createSmartPlaylistDetails"
            aria-labelledby="createSmartPlaylistTabDetails"
            class="space-y-5"
          >
            <div class="grid grid-cols-2 gap-4">
              <FormRow>
                <template #label>{{ t('smartPlaylists.name') }}</template>
                <TextInput
                  v-model="data.name"
                  v-koel-focus name="name"
                  :placeholder="t('screens.playlistNamePlaceholder')"
                  required
                />
              </FormRow>
              <FormRow>
                <template #label>{{ t('smartPlaylists.folder') }}</template>
                <SelectBox v-model="data.folder_id">
                  <option :value="null" />
                  <option v-for="folder in folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option>
                </SelectBox>
              </FormRow>
              <FormRow class="col-span-2">
                <template #label>{{ t('smartPlaylists.description') }}</template>
                <TextArea v-model="data.description" class="h-28" name="description" />
              </FormRow>
              <ArtworkField v-model="data.cover">{{ t('smartPlaylists.pickCover') }}</ArtworkField>
            </div>
          </TabPanel>

          <TabPanel
            v-show="isTabActive('rules')"
            id="createSmartPlaylistRules"
            aria-labelledby="createSmartPlaylistTabRules"
            class="space-y-5"
          >
            <div v-koel-overflow-fade class="group-container space-y-5 overflow-auto max-h-[480px]">
              <RuleGroup
                v-for="(group, index) in mutablePlaylist.rules"
                :key="group.id"
                :group="group"
                :is-first-group="index === 0"
                @input="onGroupChanged"
              />
              <Btn class="btn-add-group" small success :title="t('ui.tooltips.addNewGroup')" uppercase @click.prevent="addGroup">
                <Icon :icon="faPlus" />
                {{ t('smartPlaylists.group') }}
              </Btn>
            </div>
          </TabPanel>
        </TabPanelContainer>
      </Tabs>
    </main>

    <footer>
      <Btn type="submit">{{ t('auth.save') }}</Btn>
      <Btn class="btn-cancel" white @click.prevent="maybeClose">{{ t('auth.cancel') }}</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { reactive, toRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { cloneDeep, isEqual, pick } from 'lodash'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import type { UpdatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { eventBus } from '@/utils/eventBus'
import { useDialogBox } from '@/composables/useDialogBox'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useSmartPlaylistForm } from '@/composables/useSmartPlaylistForm'
import { useForm } from '@/composables/useForm'

import TextInput from '@/components/ui/form/TextInput.vue'
import FormRow from '@/components/ui/form/FormRow.vue'
import SelectBox from '@/components/ui/form/SelectBox.vue'
import TextArea from '@/components/ui/form/TextArea.vue'
import TabButton from '@/components/ui/tabs/TabButton.vue'
import Tabs from '@/components/ui/tabs/Tabs.vue'
import TabPanelContainer from '@/components/ui/tabs/TabPanelContainer.vue'
import TabList from '@/components/ui/tabs/TabList.vue'
import TabPanel from '@/components/ui/tabs/TabPanel.vue'
import ArtworkField from '@/components/ui/form/ArtworkField.vue'

const { t } = useI18n()
const props = defineProps<{ playlist: Playlist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { playlist } = props

const { toastSuccess } = useMessageToaster()
const { showConfirmDialog } = useDialogBox()

const folders = toRef(playlistFolderStore.state, 'folders')
const mutablePlaylist = reactive(cloneDeep(playlist))

const {
  Btn,
  RuleGroup,
  activateTab,
  isTabActive,
  collectedRuleGroups,
  addGroup,
  onGroupChanged,
} = useSmartPlaylistForm(cloneDeep(playlist.rules))

const close = () => emit('close')

const { data, isPristine, handleSubmit } = useForm<UpdatePlaylistData>({
  initialValues: pick(playlist, 'name', 'folder_id', 'description', 'cover'),
  isPristine: (original, current) => isEqual(original, current) && isEqual(collectedRuleGroups.value, playlist.rules),
  onSubmit: async data => {
    const formData = {
      ...cloneDeep(data),
      rules: collectedRuleGroups.value,
    }

    if (formData.cover === playlist.cover) {
      delete formData.cover
    }

    await playlistStore.update(playlist, formData)
  },
  onSuccess: () => {
    toastSuccess(`Playlist "${playlist.name}" updated.`)
    eventBus.emit('PLAYLIST_UPDATED', playlist)
    close()
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

form {
  max-height: calc(100vh - 4rem);
}
</style>
