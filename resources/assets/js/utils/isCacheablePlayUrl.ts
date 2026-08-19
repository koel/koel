export const isCacheablePlayUrl = (url: string): boolean => {
  try {
    const parsedUrl = new URL(url)

    if (parsedUrl.searchParams.get('progressive') === '1') {
      return false
    }

    return /\/play\/[^/]+(\/1)?$/.test(parsedUrl.pathname)
  } catch {
    return false
  }
}
