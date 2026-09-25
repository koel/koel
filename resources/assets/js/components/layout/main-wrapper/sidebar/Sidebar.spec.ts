import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { commonStore } from '@/stores/commonStore'
import { eventBus } from '@/utils/eventBus'
import Component from './Sidebar.vue'

const standardItems = ['All Songs', 'Albums', 'Artists', 'Genres', 'Favorites', 'Recently Played']

const adminItems = [...standardItems, 'Users', 'Upload', 'Settings']

describe('sidebar.vue', () => {
  const h = createHarness()

  it('shows the standard items', () => {
    h.actingAsUser().render(Component)
    standardItems.forEach(label => screen.getByText(label))
  })

  it('shows administrative items', () => {
    h.actingAsAdmin().render(Component)
    adminItems.forEach(label => screen.getByText(label))
  })

  it('shows the YouTube sidebar item on demand', async () => {
    commonStore.state.uses_you_tube = true
    h.render(Component)

    eventBus.emit('PLAY_YOUTUBE_VIDEO', { id: '123', title: 'A Random Video' })
    await h.tick()

    screen.getByText('A Random Video')
  })
})

describe('sidebar.vue temporary expand on hover', () => {
  const h = createHarness({
    beforeEach: () => {
      vi.useFakeTimers()
      localStorage.clear()
    },
  })

  afterEach(() => vi.useRealTimers())

  const nav = () => screen.getByRole('navigation')

  const renderCollapsed = async () => {
    const rendered = h.render(Component)
    await fireEvent.click(rendered.container.querySelector('.btn-toggle input[type="checkbox"]')!)

    return rendered
  }

  const hoverInUntilExpanded = async () => {
    await fireEvent.mouseEnter(nav())
    vi.advanceTimersByTime(500)
    await h.tick()
  }

  it('peeks open after hovering past the expand delay', async () => {
    await renderCollapsed()
    expect(nav().classList.contains('tmp-showing')).toBe(false)

    await hoverInUntilExpanded()

    expect(nav().classList.contains('tmp-showing')).toBe(true)
  })

  it('does not collapse the moment the cursor leaves', async () => {
    await renderCollapsed()
    await hoverInUntilExpanded()

    await fireEvent.mouseLeave(nav(), { relatedTarget: document.body })
    vi.advanceTimersByTime(200)
    await h.tick()

    expect(nav().classList.contains('tmp-showing')).toBe(true)
  })

  it('stays open when the cursor returns within the grace period', async () => {
    await renderCollapsed()
    await hoverInUntilExpanded()

    await fireEvent.mouseLeave(nav(), { relatedTarget: document.body })
    vi.advanceTimersByTime(200)
    await fireEvent.mouseEnter(nav())
    vi.advanceTimersByTime(1000)
    await h.tick()

    expect(nav().classList.contains('tmp-showing')).toBe(true)
  })

  it('collapses once the grace period elapses', async () => {
    await renderCollapsed()
    await hoverInUntilExpanded()

    await fireEvent.mouseLeave(nav(), { relatedTarget: document.body })
    vi.advanceTimersByTime(500)
    await h.tick()

    expect(nav().classList.contains('tmp-showing')).toBe(false)
  })
})
