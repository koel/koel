const MAX_PIXEL_RATIO = 2

export const getRenderPixelRatio = () => Math.min(window.devicePixelRatio, MAX_PIXEL_RATIO)
