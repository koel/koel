import { ref } from 'vue'
import { playlistStore } from '@/stores'

import Btn from '@/components/ui/Btn.vue'
import FormBase from '@/components/playlist/smart-playlist/SmartPlaylistFormBase.vue'
import RuleGroup from '@/components/playlist/smart-playlist/SmartPlaylistRuleGroup.vue'
import SoundBars from '@/components/ui/SoundBars.vue'

export const useSmartPlaylistForm = (initialRuleGroups: SmartPlaylistRuleGroup[] = []) => {
  const collectedRuleGroups = ref<SmartPlaylistRuleGroup[]>(initialRuleGroups)

  const addGroup = () => collectedRuleGroups.value.push(playlistStore.createEmptySmartPlaylistRuleGroup())

  const onGroupChanged = (data: SmartPlaylistRuleGroup) => {
    const changedGroup = Object.assign(collectedRuleGroups.value.find(g => g.id === data.id)!, data)

    // Remove empty group
    if (changedGroup.rules.length === 0) {
      collectedRuleGroups.value = collectedRuleGroups.value.filter(group => group.id !== changedGroup.id)
    }
  }

  return {
    Btn,
    FormBase,
    RuleGroup,
    SoundBars,
    collectedRuleGroups,
    addGroup,
    onGroupChanged
  }
}
