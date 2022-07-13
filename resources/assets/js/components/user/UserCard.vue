<template>
  <article :class="{ me: isCurrentUser }" class="user-card" data-testid="user-card">
    <img :alt="`${user.name}'s avatar`" :src="user.avatar" height="80" width="80">

    <main>
      <h1>
        <span class="name">{{ user.name }}</span>
        <i v-if="isCurrentUser" class="you text-orange fa fa-check-circle" title="This is you!"/>
        <i v-if="user.is_admin" class="is-admin text-blue fa fa-shield" title="User has admin privileges"/>
      </h1>

      <p class="email text-secondary">{{ user.email }}</p>

      <footer>
        <Btn class="btn-edit" data-testid="edit-user-btn" small orange @click="edit">
          {{ isCurrentUser ? 'Your Profile' : 'Edit' }}
        </Btn>
        <Btn v-if="!isCurrentUser" class="btn-delete" data-testid="delete-user-btn" small red @click="confirmDelete">
          Delete
        </Btn>
      </footer>
    </main>
  </article>
</template>

<script lang="ts" setup>
import { computed, toRefs } from 'vue'
import { userStore } from '@/stores'
import { alerts, eventBus } from '@/utils'
import { useAuthorization } from '@/composables'
import router from '@/router'

import Btn from '@/components/ui/Btn.vue'

const props = defineProps<{ user: User }>()
const { user } = toRefs(props)

const { currentUser } = useAuthorization()

const isCurrentUser = computed(() => user.value.id === currentUser.value.id)

const edit = () => isCurrentUser.value ? router.go('profile') : eventBus.emit('MODAL_SHOW_EDIT_USER_FORM', user.value)
const confirmDelete = () => alerts.confirm(`You’re about to unperson ${user.value.name}. Are you sure?`, destroy)

const destroy = async () => {
  await userStore.destroy(user.value)
  alerts.success(`User "${user.value.name}" deleted.`)
}
</script>

<style lang="scss" scoped>
.user-card {
  padding: 10px;
  display: flex;
  flex-direction: row;
  align-items: center;
  border-radius: 5px;
  background: var(--color-bg-secondary);
  border: 1px solid var(--color-bg-secondary);
  gap: 1rem;

  img {
    border-radius: 5px;
    flex: 0 0 80px;
    background: rgba(0, 0, 0, .2)
  }

  main {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    gap: .5rem;
  }

  h1 {
    font-size: 1rem;
    font-weight: var(--font-weight-normal);

    > * + * {
      margin-left: .5rem
    }
  }

  footer {
    visibility: hidden;

    > * + * {
      margin-left: .3rem;
    }

    @media (hover: none) {
      visibility: visible;
    }
  }

  &:hover footer {
    visibility: visible;
  }
}
</style>
