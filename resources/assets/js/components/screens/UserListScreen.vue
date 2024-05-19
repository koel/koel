<template>
  <ScreenBase>
    <template #header>
      <ScreenHeader layout="collapsed">
        Users
        <ControlsToggle v-model="showingControls" />

        <template #controls>
          <BtnGroup v-if="showingControls || !isPhone" uppercase>
            <Btn success @click="showAddUserForm">
              <Icon :icon="faPlus" />
              Add
            </Btn>
            <Btn v-if="canInvite" highlight @click="showInviteUserForm">Invite</Btn>
          </BtnGroup>
        </template>
      </ScreenHeader>
    </template>

    <ul class="space-y-3">
      <li v-for="user in users" :key="user.id">
        <UserCard :user="user" />
      </li>
    </ul>

    <template v-if="prospects.length">
      <h2
        class="px-0 pt-6 pb-3 uppercase tracking-widest text-center relative flex justify-center text-k-text-secondary"
        data-testid="prospects-heading"
      >
        <i class="invited-heading-decoration" />
        <span class="px-4 py-1 relative rounded-md border border-k-text-secondary">
          Invited
        </span>
        <i class="invited-heading-decoration" />
      </h2>

      <ul class="space-y-3">
        <li v-for="user in prospects" :key="user.id">
          <UserCard :user="user" />
        </li>
      </ul>
    </template>
  </ScreenBase>
</template>

<script lang="ts" setup>
import { faPlus } from '@fortawesome/free-solid-svg-icons'
import isMobile from 'ismobilejs'
import { computed, defineAsyncComponent, onMounted, ref, toRef } from 'vue'
import { userStore } from '@/stores'
import { eventBus } from '@/utils'
import { useAuthorization } from '@/composables'

import ScreenHeader from '@/components/ui/ScreenHeader.vue'
import ControlsToggle from '@/components/ui/ScreenControlsToggle.vue'
import UserCard from '@/components/user/UserCard.vue'
import BtnGroup from '@/components/ui/form/BtnGroup.vue'
import ScreenBase from '@/components/screens/ScreenBase.vue'

const Btn = defineAsyncComponent(() => import('@/components/ui/form/Btn.vue'))

const { currentUser } = useAuthorization()

const allUsers = toRef(userStore.state, 'users')

const users = computed(() => allUsers
  .value
  .filter(({ is_prospect }) => !is_prospect)
  .sort((a, b) => a.id === currentUser.value.id ? -1 : b.id === currentUser.value.id ? 1 : a.name.localeCompare(b.name))
)

const prospects = computed(() => allUsers.value.filter(({ is_prospect }) => is_prospect))

const isPhone = isMobile.phone
const showingControls = ref(false)
const canInvite = window.MAILER_CONFIGURED

const showAddUserForm = () => eventBus.emit('MODAL_SHOW_ADD_USER_FORM')
const showInviteUserForm = () => eventBus.emit('MODAL_SHOW_INVITE_USER_FORM')

onMounted(async () => await userStore.fetch())
</script>

<style lang="postcss" scoped>
.invited-heading-decoration {
  @apply relative flex-1 before:absolute before:top-1/2;
  @apply before:left-0 before:right-0 before:h-px before:opacity-20 before:bg-k-text-secondary;
}
</style>
