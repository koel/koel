export const copyStylesheetsToDocument = (target: Document) => {
  document.querySelectorAll('link[rel="stylesheet"], style').forEach(node => target.head.append(node.cloneNode(true)))
}

export const copyThemeToDocument = (target: Document) => {
  target.documentElement.className = document.documentElement.className
  target.documentElement.dataset.theme = document.documentElement.dataset.theme
  target.body.style.cssText = document.body.style.cssText
}
