<template>
  <div
    class="collaboration-modal max-w-[640px]"
    tabindex="0"
    data-testid="playlist-collaboration"
    @keydown.esc="close"
  >
    <header>
      <h1>{{ t('playlists.collaboration') }}</h1>
    </header>

    <main>
      <p>
        {{ t('playlists.collaborativeDescription') }}<br>
        {{ t('playlists.collaborativeWarning') }}
      </p>

      <section class="space-y-5">
        <h2 class="flex text-xl mt-6 mb-1 items-center">
          <span class="flex-1">{{ t('playlists.currentCollaborators') }}</span>
          <InviteCollaborators v-if="canManageCollaborators" :playlist="playlist" />
        </h2>
        <div v-koel-overflow-fade class="collaborators-wrapper overflow-auto">
          <CollaboratorList :playlist="playlist" />
        </div>
      </section>
    </main>

    <footer>
      <Btn @click.prevent="close">{{ t('playlists.close') }}</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthorization } from '@/composables/useAuthorization'

import Btn from '@/components/ui/form/Btn.vue'
import InviteCollaborators from '@/components/playlist/InvitePlaylistCollaborators.vue'
import CollaboratorList from '@/components/playlist/PlaylistCollaboratorList.vue'

const props = defineProps<{ playlist: Playlist }>()
const emit = defineEmits<{ (e: 'close'): void }>()

const { playlist } = props

const { t } = useI18n()
const { currentUser } = useAuthorization()

const canManageCollaborators = computed(() => currentUser.value?.id === playlist.owner_id)

const close = () => emit('close')
</script>

<style lang="postcss" scoped>
.collaborators-wrapper {
  max-height: calc(100vh - 8rem);
}
</style>
