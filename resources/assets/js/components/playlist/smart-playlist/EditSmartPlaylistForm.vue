<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>Edit Smart Playlist</h1>
    </header>

    <main class="space-y-5">
      <SmartPlaylistFormTabs v-model:rule-groups="ruleGroups">
        <PlaylistDetailsFields
          v-model:name="data.name"
          v-model:folder-id="data.folder_id"
          v-model:folder-name="data.folder_name"
          v-model:description="data.description"
          v-model:cover="data.cover"
        />
      </SmartPlaylistFormTabs>
    </main>

    <footer>
      <Btn :disabled="!ruleGroups.length" type="submit">Save</Btn>
      <Btn variant="ghost" class="btn-cancel" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { isEqual, pick } from 'lodash-es'
import { ref, toRaw } from 'vue'

import type { UpdatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { eventBus } from '@/utils/eventBus'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import PlaylistDetailsFields from '@/components/playlist/PlaylistDetailsFields.vue'
import SmartPlaylistFormTabs from '@/components/playlist/smart-playlist/SmartPlaylistFormTabs.vue'

const props = defineProps<{ playlist: Playlist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { playlist } = props

const { toastSuccess } = useMessageToaster()

const ruleGroups = ref<SmartPlaylistRuleGroup[]>(structuredClone(toRaw(playlist.rules)))

const close = () => emit('close')

const { data, handleSubmit, maybeClose } = useForm<UpdatePlaylistData>({
  initialValues: { ...pick(playlist, 'name', 'folder_id', 'description', 'cover'), folder_name: null },
  isPristine: (original, current) => isEqual(original, current) && isEqual(ruleGroups.value, playlist.rules),
  onSubmit: async data => {
    const formData = {
      ...structuredClone(toRaw(data)),
      rules: ruleGroups.value,
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
  onClose: close,
})
</script>

<style lang="postcss" scoped>
form {
  max-height: calc(100vh - 4rem);
}
</style>
