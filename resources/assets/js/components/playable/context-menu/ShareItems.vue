<template>
  <MenuItem v-if="canShare">
    Share
    <template #subMenuItems>
      <MenuItem v-if="canBeShared" @click="copyUrl">
        <template #icon>
          <Icon :icon="faLink" fixed-width />
        </template>
        Copy URL
      </MenuItem>
      <MenuItem v-if="allowEmbedding" @click="showEmbedModal">
        <template #icon>
          <Icon :icon="faCode" fixed-width />
        </template>
        Embed…
      </MenuItem>
    </template>
  </MenuItem>
</template>

<script lang="ts" setup>
import { faCode, faLink } from '@fortawesome/free-solid-svg-icons'
import { computed } from 'vue'
import { copyText, defineAsyncComponent } from '@/utils/helpers'
import { isSong } from '@/utils/typeGuards'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { useContextMenu } from '@/composables/useContextMenu'
import { useKoelPlus } from '@/composables/useKoelPlus'
import { useMessageToaster } from '@/composables/useMessageToaster'
import { useModal } from '@/composables/useModal'
import { usePolicies } from '@/composables/usePolicies'

const props = defineProps<{ playables: Playable[] }>()

const CreateEmbedForm = defineAsyncComponent(() => import('@/components/embed/CreateEmbedForm.vue'))

const { MenuItem, trigger } = useContextMenu()
const { toastSuccess } = useMessageToaster()
const { openModal } = useModal()
const { isPlus } = useKoelPlus()
const { currentUserCan } = usePolicies()

const playable = computed(() => props.playables[0])
const canBeShared = computed(() => !isPlus.value || (isSong(playable.value) && playable.value.is_public))
const allowEmbedding = computed(() => commonStore.state.allows_embedding && currentUserCan.createEmbeds())
const canShare = computed(() => props.playables.length === 1 && (canBeShared.value || allowEmbedding.value))

const copyUrl = () =>
  trigger(async () => {
    await copyText(playableStore.getShareableUrl(playable.value))
    toastSuccess('URL copied to clipboard.')
  })

const showEmbedModal = () =>
  trigger(() => openModal<'CREATE_EMBED_FORM'>(CreateEmbedForm, { embeddable: playable.value }))
</script>
