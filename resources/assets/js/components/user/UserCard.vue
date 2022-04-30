<template>
  <article v-if="showing" :class="{ me: isCurrentUser }" class="user-card" data-test="user-card">
    <div class="info">
      <img :alt="`${user.name}'s avatar`" :src="user.avatar" height="96" width="96">

      <div class="right">
        <div>
          <h1>
            <span class="name">{{ user.name }}</span>
            <i
              v-if="isCurrentUser"
              class="you text-orange fa fa-check-circle"
              data-test="current-user-indicator"
              title="This is you!"
            />
            <i
              v-if="user.is_admin"
              class="is-admin text-blue fa fa-shield"
              data-test="admin-indicator"
              title="User has admin privileges"
            />
          </h1>
          <p class="email" data-test="user-email">{{ user.email }}</p>
        </div>

        <div class="buttons">
          <Btn class="btn-edit" data-test="edit-user-btn" small @click="edit">{{ editButtonLabel }}</Btn>
          <Btn
            v-if="!isCurrentUser"
            class="btn-delete"
            data-test="delete-user-btn"
            red
            small
            @click="confirmDelete"
          >
            Delete
          </Btn>
        </div>
      </div>
    </div>
  </article>
</template>

<script lang="ts" setup>
import { computed, defineAsyncComponent, ref, toRefs } from 'vue'
import { userStore } from '@/stores'
import { alerts } from '@/utils'
import { useAuthorization } from '@/composables'
import router from '@/router'

const Btn = defineAsyncComponent(() => import('@/components/ui/Btn.vue'))

const props = defineProps<{ user: User }>()
const { user } = toRefs(props)

const showing = ref(true)

const { currentUser } = useAuthorization()

const isCurrentUser = computed(() => user.value.id === currentUser.value.id)
const editButtonLabel = computed(() => isCurrentUser.value ? 'Update Profile' : 'Edit')

const emit = defineEmits(['editUser'])

const edit = () => isCurrentUser.value ? router.go('profile') : emit('editUser', user.value)
const confirmDelete = () => alerts.confirm(`You’re about to unperson ${user.value.name}. Are you sure?`, destroy)

const destroy = () => {
  userStore.destroy(user.value)
  showing.value = false
  alerts.success(`User "${user.value.name}" deleted.`)
}
</script>

<style lang="scss" scoped>
.user-card {
  width: 100%;

  .info {
    display: flex;

    img {
      flex: 0 0 96px;
    }

    .email {
      opacity: .5;
    }

    .right {
      flex: 1;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background-color: rgba(255, 255, 255, .02);
      position: relative;
    }

    h1 {
      font-size: 1.4rem;
      margin-bottom: .25rem;

      > * + * {
        margin-left: .5rem
      }
    }

    .buttons {
      display: none;
      margin-top: .5rem;

      > * + * {
        margin-left: .25rem;
      }

      @media (hover: none) {
        display: block;
      }
    }

    &:hover .buttons {
      display: block;
    }
  }

  @media only screen and (max-width: 1024px) {
    width: 100%;
  }
}
</style>
