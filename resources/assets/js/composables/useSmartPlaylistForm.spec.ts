import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { useSmartPlaylistForm } from './useSmartPlaylistForm'
import { playlistStore } from '@/stores/playlistStore'

describe('useSmartPlaylistForm', () => {
  createHarness()

  it('starts on the details tab', () => {
    const { currentTab, isTabActive } = useSmartPlaylistForm()
    expect(currentTab.value).toBe('details')
    expect(isTabActive('details')).toBe(true)
    expect(isTabActive('rules')).toBe(false)
  })

  it('switches tabs', () => {
    const { activateTab, isTabActive } = useSmartPlaylistForm()
    activateTab('rules')
    expect(isTabActive('rules')).toBe(true)
    expect(isTabActive('details')).toBe(false)
  })

  it('initializes with provided rule groups', () => {
    const groups = [{ id: 1, rules: [] }] as unknown as SmartPlaylistRuleGroup[]
    const { collectedRuleGroups } = useSmartPlaylistForm(groups)
    expect(collectedRuleGroups.value).toHaveLength(1)
  })

  it('adds a new rule group', () => {
    const mockGroup = { id: 99, rules: [{}] } as unknown as SmartPlaylistRuleGroup
    vi.spyOn(playlistStore, 'createEmptySmartPlaylistRuleGroup').mockReturnValue(mockGroup)

    const { collectedRuleGroups, addGroup } = useSmartPlaylistForm()
    addGroup()
    expect(collectedRuleGroups.value).toHaveLength(1)
    expect(collectedRuleGroups.value[0].id).toBe(99)
  })

  it('removes a group when its rules become empty', () => {
    const groups = [
      { id: 1, rules: [{ id: 'r1' }] },
      { id: 2, rules: [{ id: 'r2' }] },
    ] as unknown as SmartPlaylistRuleGroup[]

    const { collectedRuleGroups, onGroupChanged } = useSmartPlaylistForm(groups)
    expect(collectedRuleGroups.value).toHaveLength(2)

    onGroupChanged({ id: 1, rules: [] } as unknown as SmartPlaylistRuleGroup)
    expect(collectedRuleGroups.value).toHaveLength(1)
    expect(collectedRuleGroups.value[0].id).toBe(2)
  })

  it('updates a group when rules are changed', () => {
    const groups = [{ id: 1, rules: [{ id: 'r1', value: 'old' }] }] as unknown as SmartPlaylistRuleGroup[]

    const { collectedRuleGroups, onGroupChanged } = useSmartPlaylistForm(groups)

    onGroupChanged({ id: 1, rules: [{ id: 'r1', value: 'new' }] } as unknown as SmartPlaylistRuleGroup)
    expect((collectedRuleGroups.value[0].rules[0] as any).value).toBe('new')
  })
})
