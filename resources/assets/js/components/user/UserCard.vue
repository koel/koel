<template>
  <article
    :class="{ me: isCurrentUser }"
    class="apply p-4 flex items-center rounded-md bg-k-bg-secondary border border-k-border
    gap-3 transition-[border-color] duration-200 ease-in-out hover:border-white/15"
  >
    <UserAvatar :user="user" width="48" />

    <main class="flex flex-col justify-between relative flex-1 gap-1">
      <h3 class="font-medium flex gap-2 items-center">
        <span v-if="user.name" class="name">{{ user.name }}</span>
        <span v-else class="name font-light text-k-text-secondary">Anonymous</span>
        <Icon v-if="isCurrentUser" :icon="faCircleCheck" class="you text-k-highlight" title="This is you!" />
        <Icon
          v-if="hasAdminPrivileges"
          :icon="faShield"
          class="is-admin text-k-primary"
          title="User has admin privileges"
        />
        <img
          v-if="user.sso_provider === 'Google'"
          :src="googleLogo"
          alt="Google"
          height="14"
          title="Google SSO"
          width="14"
        >
      </h3>

      <p class="text-k-text-secondary">{{ user.email }}</p>
    </main>

    <Btn v-if="isCurrentUser" :href="url('profile')" highlight small tag="a">Your Profile</Btn>

    <Btn v-else gray @click="requestContextMenu">
      <Icon :icon="faEllipsis" fixed-width />
      <span class="sr-only">More Actions</span>
    </Btn>
  </article>
</template>

<script lang="ts" setup>
import googleLogo from '@/../img/logos/google.svg'
import { faCircleCheck, faEllipsis, faShield } from '@fortawesome/free-solid-svg-icons'
import { computed, toRefs } from 'vue'
import { useRouter } from '@/composables/useRouter'
import { useAuthorization } from '@/composables/useAuthorization'
import { useContextMenu } from '@/composables/useContextMenu'

import Btn from '@/components/ui/form/Btn.vue'
import UserAvatar from '@/components/user/UserAvatar.vue'
import UserContextMenu from '@/components/user/UserContextMenu.vue'

const props = defineProps<{ user: User }>()
const { user } = toRefs(props)

const { url } = useRouter()
const { openContextMenu } = useContextMenu()

const { currentUser } = useAuthorization()

const isCurrentUser = computed(() => user.value.id === currentUser.value.id)
const hasAdminPrivileges = computed(() => user.value.role === 'admin' || user.value.role === 'manager')

const requestContextMenu = (event: MouseEvent) => openContextMenu<'USER'>(UserContextMenu, event, {
  user: user.value,
})
</script>
