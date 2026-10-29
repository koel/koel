<template>
  <form class="md:w-[560px]" @submit.prevent="handleSubmit" @keydown.esc="maybeClose">
    <header>
      <h1>New Smart Playlist</h1>
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
      <Btn :disabled="loading || !ruleGroups.length" type="submit">Save</Btn>
      <Btn variant="neutral" class="btn-cancel" @click.prevent="maybeClose">Cancel</Btn>
    </footer>
  </form>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash-es'
import { ref } from 'vue'
import type { CreatePlaylistData } from '@/stores/playlistStore'
import { playlistStore } from '@/stores/playlistStore'
import { useRouter } from '@/composables/useRouter'
import { useForm } from '@/composables/useForm'

import Btn from '@/components/ui/form/Btn.vue'
import PlaylistDetailsFields from '@/components/playlist/PlaylistDetailsFields.vue'
import SmartPlaylistFormTabs from '@/components/playlist/smart-playlist/SmartPlaylistFormTabs.vue'

const props = defineProps<{ folder?: PlaylistFolder | null }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { folder: targetFolder } = props

const initialRuleGroups = [playlistStore.createEmptySmartPlaylistRuleGroup()]

const ruleGroups = ref<SmartPlaylistRuleGroup[]>(structuredClone(initialRuleGroups))

const { go, url } = useRouter()

const close = () => emit('close')

const { data, loading, handleSubmit, maybeClose } = useForm<CreatePlaylistData>({
  initialValues: {
    name: '',
    description: '',
    folder_id: targetFolder?.id || null,
    folder_name: null,
    cover: null,
  },
  isPristine: (original, current) => isEqual(original, current) && isEqual(ruleGroups.value, initialRuleGroups),
  toast: {
    loading: 'Creating playlist…',
    success: (playlist: Playlist) => `Playlist "${playlist.name}" created.`,
  },
  onSubmit: async data =>
    await playlistStore.store({
      ...data,
      rules: ruleGroups.value,
    }),
  onSuccess: (playlist: Playlist) => {
    close()
    go(url('playlists.show', { id: playlist.id }))
  },
  onClose: close,
})
</script>

<style lang="postcss" scoped>
form {
  max-height: calc(100vh - 4rem);
}
</style>
