<template>
  <div
    class="collaboration-modal max-w-[640px]"
    tabindex="0"
    @keydown.esc="close"
    data-testid="playlist-collaboration"
  >
    <header>
      <h1>Playlist Collaboration</h1>
    </header>

    <main>
      <p class="text-k-text-secondary">
        Collaborative playlists allow multiple users to contribute. <br>
        Note: Songs added to a collaborative playlist are made accessible to all users,
        and you cannot mark a song as private if it’s still part of a collaborative playlist.
      </p>

      <section class="space-y-5">
        <h2 class="flex text-xl mt-6 mb-1 items-center">
          <span class="flex-1">Current Collaborators</span>
          <InviteCollaborators v-if="canManageCollaborators" :playlist="playlist" />
        </h2>
        <div v-koel-overflow-fade class="collaborators-wrapper overflow-auto">
          <CollaboratorList :playlist="playlist" />
        </div>
      </section>
    </main>

    <footer>
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { useAuthorization, useModal } from '@/composables'

import Btn from '@/components/ui/form/Btn.vue'
import InviteCollaborators from '@/components/playlist/InvitePlaylistCollaborators.vue'
import CollaboratorList from '@/components/playlist/PlaylistCollaboratorList.vue'

const playlist = useModal().getFromContext<Playlist>('playlist')
const { currentUser } = useAuthorization()

const canManageCollaborators = computed(() => currentUser.value?.id === playlist.user_id)

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')
</script>

<style lang="postcss" scoped>
.collaborators-wrapper {
  max-height: calc(100vh - 8rem);
}
</style>
