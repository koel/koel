<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Users

        <template #controls>
          <BtnGroup uppercase>
            <Btn variant="success" @click="showAddUserForm">
              <Icon :icon="faPlus" />
              Add
            </Btn>
            <Btn variant="highlight" v-if="canInvite" @click="showInviteUserForm">Invite</Btn>
          </BtnGroup>
        </template>
      </ScreenHeader>
    </template>

    <ul class="space-y-3">
      <li v-for="user in users" :key="user.id">
        <UserCard :user />
      </li>
    </ul>

    <template v-if="prospects.length">
      <h2
        class="px-0 pt-6 pb-3 uppercase tracking-widest text-center relative flex justify-center"
        data-testid="prospects-heading"
      >
        <i class="invited-heading-decoration" />
        <span class="px-4 py-1 relative">Invited</span>
        <i class="invited-heading-decoration" />
      </h2>

      <ul class="space-y-3">
        <li v-for="user in prospects" :key="user.id">
          <UserCard :user />
        </li>
      </ul>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import { computed, onMounted, toRef } from 'vue'
import { userStore } from '@/stores/userStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { useAuthorization } from '@/composables/useAuthorization'
import { useModal } from '@/composables/useModal'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import UserCard from '@/components/user/UserCard.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))
const AddUserForm = defineAsyncComponent(() => import('@/components/user/AddUserForm.vue'))
const InviteUserForm = defineAsyncComponent(() => import('@/components/user/InviteUserForm.vue'))

const { openModal } = useModal()
const { currentUser } = useAuthorization()

const allUsers = toRef(userStore.state, 'users')

const users = computed(() =>
  allUsers.value
    .filter(({ is_prospect }) => !is_prospect)
    .sort((a, b) =>
      a.id === currentUser.value.id ? -1 : b.id === currentUser.value.id ? 1 : a.name.localeCompare(b.name),
    ),
)

const prospects = computed(() => allUsers.value.filter(({ is_prospect }) => is_prospect))

const canInvite = window.KOEL.mailer_configured

const showAddUserForm = () => openModal<'ADD_USER_FORM'>(AddUserForm)
const showInviteUserForm = () => openModal<'INVITE_USER_FORM'>(InviteUserForm)

onMounted(async () => await userStore.fetch())
</script>

<style lang="postcss" scoped>
.invited-heading-decoration {
  @apply relative flex-1 before:absolute before:top-1/2;
  @apply before:left-0 before:right-0 before:h-px before:opacity-20 before:bg-k-fg-70;
}
</style>
