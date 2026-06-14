import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { cache } from '@/services/cache'

interface BrowseFoldersResponse {
  current: Folder | null
  ancestors: Folder[]
  subfolders: Folder[]
}

export const mediaBrowser = {
  async browse(folderId: string | null, cursor: string | null = '', forceRefresh = false) {
    if (forceRefresh) {
      cache.remove(['folder', folderId, 'folders'])
      cache.remove(['folder', folderId, 'songs', cursor])
    }

    const query = new URLSearchParams()
    if (folderId) {
      query.set('folder', folderId)
    }
    query.set('cursor', cursor ?? '')

    const [folders, paginator] = await Promise.all([
      cache.remember(['folder', folderId, 'folders'], () =>
        http.get<BrowseFoldersResponse>(`browse/folders${folderId ? `?folder=${folderId}` : ''}`),
      ),
      cache.remember(['folder', folderId, 'songs', cursor], () =>
        http.get<CursorPaginatorResource<Song>>(`browse/songs?${query}`),
      ),
    ])

    return {
      current: folders.current,
      ancestors: folders.ancestors,
      subfolders: folders.subfolders,
      songs: playableStore.syncWithVault(paginator.data) as Song[],
      nextCursor: paginator.meta.next_cursor,
    }
  },

  getParentReference(folder: Folder | null): Folder | null {
    if (!folder) {
      return null
    }

    return {
      type: 'folders',
      id: folder.parent_id ?? '',
      parent_id: null,
      name: '..',
      is_uploads: false,
    }
  },

  extractMediaReferences(items: (Song | Folder)[]) {
    return items.map<MediaReference>(item => ({
      type: item.type,
      id: item.id,
    }))
  },
}
