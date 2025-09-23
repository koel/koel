import { ref } from 'vue'
import { playlistStore } from '@/stores/playlistStore'

import Btn from '@/components/ui/form/Btn.vue'
import RuleGroup from '@/components/playlist/smart-playlist/SmartPlaylistRuleGroup.vue'
import SoundBars from '@/components/ui/SoundBars.vue'

type SmartPlaylistFormTab = 'details' | 'rules'

export const useSmartPlaylistForm = (initialRuleGroups: SmartPlaylistRuleGroup[] = []) => {
  const currentTab = ref<SmartPlaylistFormTab>('details')
  const activateTab = (tab: SmartPlaylistFormTab) => currentTab.value = tab
  const isTabActive = (tab: SmartPlaylistFormTab) => currentTab.value === tab

  const collectedRuleGroups = ref<SmartPlaylistRuleGroup[]>(initialRuleGroups)

  const addGroup = () => collectedRuleGroups.value.push(playlistStore.createEmptySmartPlaylistRuleGroup())

  const onGroupChanged = (data: SmartPlaylistRuleGroup) => {
    const changedGroup = Object.assign(collectedRuleGroups.value.find(({ id }) => id === data.id)!, data)

    // Remove empty groups
    if (changedGroup.rules.length === 0) {
      collectedRuleGroups.value = collectedRuleGroups.value.filter(({ id }) => id !== changedGroup.id)
    }
  }

  return {
    Btn,
    RuleGroup,
    SoundBars,
    currentTab,
    activateTab,
    isTabActive,
    collectedRuleGroups,
    addGroup,
    onGroupChanged,
  }
}
