import { http } from '@/services/http'
import { playableStore } from '@/stores/playableStore'
import { cache } from '@/services/cache'

interface BrowseFoldersResponse {
  current: Folder | null
  ancestors: Folder[]
  subfolders: Folder[]
}

export const mediaBrowser = {
  async browse(folderId: string | null, page = 1, forceRefresh = false) {
    if (forceRefresh) {
      cache.remove(['folder', folderId, 'folders'])
      cache.remove(['folder', folderId, 'songs', page])
    }

    const query = folderId ? `folder=${folderId}` : ''

    const [folders, paginator] = await Promise.all([
      cache.remember(['folder', folderId, 'folders'], () =>
        http.get<BrowseFoldersResponse>(`browse/folders${query ? `?${query}` : ''}`),
      ),
      cache.remember(['folder', folderId, 'songs', page], () =>
        http.get<PaginatorResource<Song>>(`browse/songs?${query ? `${query}&` : ''}page=${page}`),
      ),
    ])

    return {
      current: folders.current,
      ancestors: folders.ancestors,
      subfolders: folders.subfolders,
      songs: playableStore.syncWithVault(paginator.data) as Song[],
      nextPage: paginator.links.next ? paginator.meta.current_page + 1 : null,
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
