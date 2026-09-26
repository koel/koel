export const usesCleanUrls = () => Boolean(window.KOEL?.clean_urls)

export const basePath = () => new URL(window.KOEL?.base_url ?? '/', location.origin).pathname

export const toClientPath = (path: string) => {
  path = path.replace(/^\/?#/, '')

  if (usesCleanUrls() && path.startsWith(basePath())) {
    path = path.substring(basePath().length)
  }

  return path.startsWith('/') ? path : `/${path}`
}

export const clientUrl = (path: string) =>
  `${window.KOEL.base_url}${usesCleanUrls() ? '' : '#/'}${toClientPath(path).substring(1)}`
