<template>
  <article class="user-card" :class="{ me: isCurrentUser }" data-test="user-card" v-if="showing">
    <div class="info">
      <img :src="user.avatar" width="96" height="96" :alt="`${user.name}'s avatar`">

      <div class="right">
        <div>
          <h1>
            <span class="name">{{ user.name }}</span>
            <i
              v-if="isCurrentUser"
              class="you text-orange fa fa-check-circle"
              title="This is you!"
              data-test="current-user-indicator"
            />
            <i
              v-if="user.is_admin"
              class="is-admin text-blue fa fa-shield"
              title="User has admin privileges"
              data-test="admin-indicator"
            />
          </h1>
          <p class="email" data-test="user-email">{{ user.email }}</p>
        </div>

        <div class="buttons">
          <Btn class="btn-edit" @click="edit" small data-test="edit-user-btn">{{ editButtonLabel }}</Btn>
          <Btn
            v-if="!isCurrentUser"
            class="btn-delete"
            red
            @click="confirmDelete"
            small
            data-test="delete-user-btn"
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
import router from '@/router'
import { alerts } from '@/utils'

const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))

const props = defineProps<{ user: User }>()
const { user } = toRefs(props)

const showing = ref(true)

const isCurrentUser = computed(() => user.value.id === userStore.current.id)
const editButtonLabel = computed(() => isCurrentUser.value ? 'Update Profile' : 'Edit')

const emit = defineEmits(['editUser'])

const edit = () => isCurrentUser.value ? router.go('profile') : emit('editUser', user.value)
const confirmDelete = () => alerts.confirm(`You’re about to unperson ${user.value.name}. Are you sure?`, destroy)

const destroy = () => {
  userStore.destroy(user.value)
  showing.value = false
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
