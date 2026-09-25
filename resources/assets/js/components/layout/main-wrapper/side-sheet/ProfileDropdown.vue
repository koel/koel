<template>
  <div ref="containerEl" class="relative flex items-center">
    <button
      class="rounded-full cursor-pointer active:scale-95 h-[42px] aspect-square"
      data-testid="profile-dropdown-trigger"
      type="button"
      @click="open = !open"
    >
      <UserAvatar
        v-if="currentUser"
        :user="currentUser"
        class="w-full h-full p-0.5 border border-solid border-k-fg-10 transition duration-200 ease-in-out hover:border-k-highlight"
      />
    </button>

    <ul v-if="open" v-koel-focus class="context-menu" tabindex="0" @keydown.esc="open = false">
      <template v-for="(item, index) in items" :key="item.id">
        <li v-if="index" class="separator" />
        <ContextMenuItem :data-testid="`profile-menu-${item.id}`" @click="choose(item)">{{
          item.label()
        }}</ContextMenuItem>
      </template>
    </ul>
  </div>
</template>

<script lang="ts">
export interface ProfileMenuItem {
  id: string
  label: () => string
  action: () => void
}
</script>

<script lang="ts" setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { eventBus } from '@/utils/eventBus'
import { useAuthorization } from '@/composables/useAuthorization'
import { useRouter } from '@/composables/useRouter'
import { useNewVersionNotification } from '@/composables/useNewVersionNotification'
import { useBranding } from '@/composables/useBranding'
import { useModal } from '@/composables/useModal'
import { defineAsyncComponent } from '@/utils/helpers'
import { Filter } from '@/config/hooks'
import { applyFilters } from '@/hooks'

import ContextMenuItem from '@/components/ui/context-menu/ContextMenuItem.vue'
import UserAvatar from '@/components/user/UserAvatar.vue'

const AboutKoelModal = defineAsyncComponent(() => import('@/components/meta/AboutKoelModal.vue'))

const { go, url } = useRouter()
const { currentUser } = useAuthorization()
const { shouldNotifyNewVersion } = useNewVersionNotification()
const { name: appName } = useBranding()
const { openModal } = useModal()

const containerEl = ref<HTMLDivElement>()
const open = ref(false)

const close = () => (open.value = false)

const openAbout = () => openModal<'ABOUT_KOEL'>(AboutKoelModal)

const items = computed(() =>
  applyFilters<ProfileMenuItem[]>(Filter.PROFILE_MENU_ITEMS, [
    { id: 'profile', label: () => 'Profile & Preferences', action: () => go(url('profile')) },
    { id: 'logout', label: () => 'Log Out', action: () => eventBus.emit('LOG_OUT') },
    {
      id: 'about',
      label: () => (shouldNotifyNewVersion.value ? 'New version available!' : `About ${appName}`),
      action: openAbout,
    },
  ]),
)

const choose = (item: ProfileMenuItem) => {
  close()
  item.action()
}

const onClickOutside = (e: MouseEvent) => {
  if (open.value && containerEl.value && !containerEl.value.contains(e.target as Node)) {
    close()
  }
}

onMounted(() => document.addEventListener('click', onClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.context-menu {
  @apply absolute right-0 top-full mt-2 md:right-full md:bottom-0 md:top-auto md:mt-0 md:mr-3;
}
</style>
