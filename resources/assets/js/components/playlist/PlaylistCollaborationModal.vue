<template>
  <div class="collaboration-modal" tabindex="0" @keydown.esc="close">
    <header>
      <h1>Playlist Collaboration</h1>
    </header>

    <main>
      <p class="intro text-secondary">
        Collaborative playlists allow multiple users to contribute. <br>
        Please note that songs added to a collaborative playlist are made accessible to all users,
        and you cannot mark a song as private if it’s still part of a collaborative playlist.
      </p>

      <section class="collaborators">
        <h2>
          <span>Current Collaborators</span>
          <InviteCollaborators v-if="canManageCollaborators" :playlist="playlist" />
        </h2>
        <div v-koel-overflow-fade class="collaborators-wrapper">
          <CollaboratorList :playlist="playlist" />
        </div>
      </section>
    </main>

    <footer>
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, Ref } from 'vue'
import { useAuthorization, useModal, useDialogBox } from '@/composables'

import Btn from '@/components/ui/Btn.vue'
import InviteCollaborators from '@/components/playlist/InvitePlaylistCollaborators.vue'
import CollaboratorList from '@/components/playlist/PlaylistCollaboratorList.vue'

const playlist = useModal().getFromContext<Playlist>('playlist')
const { currentUser } = useAuthorization()
const { showConfirmDialog } = useDialogBox()

let collaborators: Ref<PlaylistCollaborator[]> = ref([])

const canManageCollaborators = computed(() => currentUser.value?.id === playlist.user_id)

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')
</script>

<style lang="scss" scoped>
.collaboration-modal {
  max-width: 640px;
}

h2 {
  display: flex;
  font-size: 1.2rem;
  margin: 1.5rem 0;

  span:first-child {
    flex: 1;
  }
}

.collaborators-wrapper {
  max-height: calc(100vh - 8rem);
  overflow-y: auto;
}
</style>
