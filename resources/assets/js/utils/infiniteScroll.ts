const screensLeftToFetchMore = 2

export const isNearScrollEnd = (scroller: HTMLElement) => {
  const remainingDistance = scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight

  return remainingDistance <= scroller.clientHeight * screensLeftToFetchMore
}
