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
          <span v-if="canManageCollaborators">
            <Btn v-if="shouldShowInviteButton" green small @click.prevent="inviteCollaborators">Invite</Btn>
            <span v-if="justCreatedInviteLink" class="text-secondary copied">
              <Icon :icon="faCheckCircle" class="text-green" />
              Invite link copied to clipboard!
            </span>
            <Icon v-if="creatingInviteLink" :icon="faCircleNotch" class="text-green" spin />
          </span>
        </h2>

        <ul v-koel-overflow-fade>
          <li v-for="user in collaborators" :key="user.id">
            <span class="avatar">
              <UserAvatar :user="user" width="32" />
            </span>
            <span class="name">
              {{ user.name }}
              <Icon
                v-if="user.id === currentUser.id"
                :icon="faCircleCheck"
                class="you text-highlight"
                title="This is you!"
              />
            </span>
            <span class="role text-secondary">
              <span v-if="user.id === playlist.user_id" class="owner">Owner</span>
              <span v-else class="contributor">Contributor</span>
            </span>
            <span v-if="canManageCollaborators" class="actions">
              <Btn v-if="user.id !== playlist.user_id" small red @click.prevent="removeCollaborator(user)">
                Remove
              </Btn>
            </span>
          </li>
        </ul>
      </section>
    </main>

    <footer>
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { faCheckCircle, faCircleCheck, faCircleNotch } from '@fortawesome/free-solid-svg-icons'
import { sortBy } from 'lodash'
import { computed, onMounted, ref, Ref } from 'vue'
import { copyText, eventBus, logger } from '@/utils'
import { playlistCollaborationService } from '@/services'
import { useAuthorization, useModal, useDialogBox } from '@/composables'

import UserAvatar from '@/components/user/UserAvatar.vue'
import Btn from '@/components/ui/Btn.vue'

const playlist = useModal().getFromContext<Playlist>('playlist')
const { currentUser } = useAuthorization()
const { showConfirmDialog } = useDialogBox()

let collaborators: Ref<PlaylistCollaborator[]> = ref([])
const creatingInviteLink = ref(false)
const justCreatedInviteLink = ref(false)
const shouldShowInviteButton = computed(() => !creatingInviteLink.value && !justCreatedInviteLink.value)

const canManageCollaborators = computed(() => currentUser.value?.id === playlist.user_id)

const emit = defineEmits<{ (e: 'close'): void }>()
const close = () => emit('close')

const fetchCollaborators = async () => {
  collaborators.value = sortBy(
    await playlistCollaborationService.getCollaborators(playlist),
    ({ id }) => {
      if (id === currentUser.value.id) return 0
      if (id === playlist.user_id) return 1
      return 2
    }
  )
}

const inviteCollaborators = async () => {
  creatingInviteLink.value = true

  try {
    await copyText(await playlistCollaborationService.createInviteLink(playlist))
    justCreatedInviteLink.value = true
    setTimeout(() => (justCreatedInviteLink.value = false), 5_000)
  } finally {
    creatingInviteLink.value = false
  }
}

const removeCollaborator = async (collaborator: PlaylistCollaborator) => {
  const deadSure = await showConfirmDialog(
    `Remove ${collaborator.name} as a collaborator? This will remove their contributions as well.`
  )

  if (!deadSure) return

  try {
    collaborators.value = collaborators.value.filter(({ id }) => id !== collaborator.id)
    await playlistCollaborationService.removeCollaborator(playlist, collaborator)
    eventBus.emit('PLAYLIST_COLLABORATOR_REMOVED', playlist)
  } catch (e) {
    logger.error(e)
  }
}

onMounted(async () => await fetchCollaborators())
</script>

<style lang="scss" scoped>
.collaboration-modal {
  max-width: 640px;
}

h2 {
  display: flex;

  span:first-child {
    flex: 1;
  }

  .copied {
    font-size: .95rem;
  }
}

.collaborators {
  h2 {
    font-size: 1.2rem;
    margin: 1.5rem 0;
  }

  ul {
    display: flex;
    width: 100%;
    flex-direction: column;
    margin: 1rem 0;
    gap: .5rem;
    max-height: calc(100vh - 8rem);
    overflow-y: auto;

    li {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      gap: 1rem;
      background: var(--color-bg-secondary);
      border: 1px solid var(--color-bg-secondary);
      padding: .5rem .8rem;
      border-radius: 5px;
      transition: border-color .2s ease-in-out;

      &:hover {
        border-color: rgba(255, 255, 255, .15);
      }

      .you {
        margin-left: .5rem;
      }

      span {
        display: inline-block;
        min-width: 0;
        line-height: 1;
      }

      .name {
        flex: 1;
      }

      .role {
        text-align: right;
        flex: 0 0 96px;
        text-transform: uppercase;

        span {
          padding: 3px 4px;
          border-radius: 4px;
          border: 1px solid rgba(255, 255, 255, .2);
        }
      }

      .actions {
        flex: 0 0 72px;
        text-align: right;
      }
    }
  }
}
</style>
