import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { useDraggable, useDroppable } from './useDragAndDrop'

describe('useDragAndDrop', () => {
  const h = createHarness()

  const createDragEvent = (types: string[] = [], data: Record<string, string> = {}): DragEvent => {
    return {
      dataTransfer: {
        effectAllowed: '',
        types,
        setData: vi.fn(),
        getData: vi.fn((key: string) => data[key] || ''),
        setDragImage: vi.fn(),
      },
    } as unknown as DragEvent
  }

  describe('useDraggable', () => {
    it('sets drag data for a single playable', () => {
      const { startDragging } = useDraggable('playables')
      const song = h.factory('song')
      const event = createDragEvent()

      startDragging(event, song)

      expect(event.dataTransfer!.setData).toHaveBeenCalledWith(
        'application/x-koel.playables',
        JSON.stringify([song.id]),
      )
    })

    it('sets drag data for an album', () => {
      const { startDragging } = useDraggable('album')
      const album = h.factory('album')
      const event = createDragEvent()

      startDragging(event, album)

      expect(event.dataTransfer!.setData).toHaveBeenCalledWith('application/x-koel.album', JSON.stringify(album.id))
    })

    it('sets drag data for an artist', () => {
      const { startDragging } = useDraggable('artist')
      const artist = h.factory('artist')
      const event = createDragEvent()

      startDragging(event, artist)

      expect(event.dataTransfer!.setData).toHaveBeenCalledWith('application/x-koel.artist', JSON.stringify(artist.id))
    })

    it('sets drag data for a playlist', () => {
      const { startDragging } = useDraggable('playlist')
      const playlist = h.factory('playlist')
      const event = createDragEvent()

      startDragging(event, playlist)

      expect(event.dataTransfer!.setData).toHaveBeenCalledWith(
        'application/x-koel.playlist',
        JSON.stringify(playlist.id),
      )
    })

    it('does nothing without dataTransfer', () => {
      const { startDragging } = useDraggable('playables')
      const event = { dataTransfer: null } as unknown as DragEvent
      expect(() => startDragging(event, h.factory('song'))).not.toThrow()
    })

    it('creates a ghost drag image', () => {
      const { startDragging } = useDraggable('playables')
      const song = h.factory('song')
      const event = createDragEvent()

      startDragging(event, song)

      expect(event.dataTransfer!.setDragImage).toHaveBeenCalled()
    })
  })

  describe('useDroppable', () => {
    it('accepts drops of configured types', () => {
      const { acceptsDrop } = useDroppable(['playables', 'album'])
      const event = createDragEvent(['application/x-koel.playables'])
      expect(acceptsDrop(event)).toBe(true)
    })

    it('rejects drops of unconfigured types', () => {
      const { acceptsDrop } = useDroppable(['playables'])
      const event = createDragEvent(['application/x-koel.album'])
      expect(acceptsDrop(event)).toBe(false)
    })

    it('parses dropped data', () => {
      const { getDroppedData } = useDroppable(['playables'])
      const event = createDragEvent(['application/x-koel.playables'], {
        'application/x-koel.playables': JSON.stringify(['id-1', 'id-2']),
      })

      expect(getDroppedData(event)).toEqual(['id-1', 'id-2'])
    })

    it('returns null for unknown drag type', () => {
      const { getDroppedData } = useDroppable(['playables'])
      const event = createDragEvent([])
      expect(getDroppedData(event)).toBeNull()
    })
  })
})
