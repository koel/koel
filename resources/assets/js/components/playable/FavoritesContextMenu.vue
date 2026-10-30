<template>
  <ul>
    <MenuItem v-if="downloadable" @click="download">Download</MenuItem>
    <ContextMenuOfflineItem :playables collection-name="Favorites" />
    <template v-if="allowEmbedding">
      <Separator />
      <MenuItem @click="showEmbedModal">Embed…</MenuItem>
    </template>
  </ul>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { commonStore } from '@/stores/commonStore'
import { userStore } from '@/stores/userStore'
import { defineAsyncComponent } from '@/utils/helpers'
import { useContextMenu } from '@/composables/useContextMenu'
import { useDownload } from '@/composables/useDownload'
import { useModal } from '@/composables/useModal'
import { usePolicies } from '@/composables/usePolicies'

import ContextMenuOfflineItem from '@/components/ui/context-menu/ContextMenuOfflineItem.vue'

const props = defineProps<{ playables: Playable[] }>()

const CreateEmbedForm = defineAsyncComponent(() => import('@/components/embed/CreateEmbedForm.vue'))

const { MenuItem, Separator, trigger } = useContextMenu()
const { fromFavorites } = useDownload()
const { openModal } = useModal()
const { currentUserCan } = usePolicies()

const allowEmbedding = computed(() => commonStore.state.allows_embedding && currentUserCan.createEmbeds())

const downloadable = computed(
  () =>
    commonStore.state.allows_download &&
    props.playables.length > 0 &&
    (props.playables.length === 1 || commonStore.state.supports_batch_downloading),
)

const download = () => trigger(() => fromFavorites())

const showEmbedModal = () =>
  trigger(() =>
    openModal<'CREATE_EMBED_FORM'>(CreateEmbedForm, {
      embeddable: { type: 'favorites', owner_name: userStore.current.name },
    }),
  )
</script>
