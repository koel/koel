<template>
  <a v-if="currentUser" class="view-profile rounded-full" data-testid="view-profile-link" :href="url('profile')">
    <UserAvatar
      :user="currentUser"
      class="p-0.5 border border-solid border-k-fg-10 transition duration-200 ease-in-out hover:border-k-highlight active:scale-95"
      width="40"
    />
    <span class="sr-only">Profile and preferences</span>
  </a>
</template>

<script lang="ts" setup>
import { useAuthorization } from '@/composables/useAuthorization'
import { useRouter } from '@/composables/useRouter'

import UserAvatar from '@/components/user/UserAvatar.vue'

const { url } = useRouter()
const { currentUser } = useAuthorization()
</script>
