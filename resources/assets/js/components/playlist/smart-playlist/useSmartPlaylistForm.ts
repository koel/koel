import { defineAsyncComponent, ref } from 'vue'
import { playlistStore } from '@/stores'

export const useSmartPlaylistForm = (initialRuleGroups: SmartPlaylistRuleGroup[] = []) => {
  const Btn = defineAsyncComponent(() => import('@/components/ui/btn.vue'))
  const FormBase = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistFormBase.vue'))
  const RuleGroup = defineAsyncComponent(() => import('@/components/playlist/smart-playlist/SmartPlaylistRuleGroup.vue'))
  const SoundBar = defineAsyncComponent(() => import('@/components/ui/sound-bar.vue'))

  const collectedRuleGroups = ref<SmartPlaylistRuleGroup[]>(initialRuleGroups)
  const loading = ref(false)

  const createGroup = () => playlistStore.createEmptySmartPlaylistRuleGroup()
  const addGroup = () => collectedRuleGroups.value.push(createGroup())

  const onGroupChanged = (data: SmartPlaylistRuleGroup) => {
    const changedGroup = Object.assign(collectedRuleGroups.value.find(g => g.id === data.id), data)

    // Remove empty group
    if (changedGroup.rules.length === 0) {
      collectedRuleGroups.value = collectedRuleGroups.value.filter(group => group.id !== changedGroup.id)
    }
  }

  return {
    Btn,
    FormBase,
    RuleGroup,
    SoundBar,
    collectedRuleGroups,
    loading,
    addGroup,
    onGroupChanged
  }
}
