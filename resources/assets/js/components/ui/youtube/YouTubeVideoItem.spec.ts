import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import Component from './YouTubeVideoItem.vue'

describe('youTubeVideoItem.vue', () => {
  const h = createHarness()

  const renderComponent = () => {
    const video = {
      id: {
        videoId: 'cLgJQ8Zj3AA',
      },
      snippet: {
        title: 'Guess what it is',
        description: 'From the LA Opening Gala 2014: John Williams Celebration',
        thumbnails: {
          default: {
            url: 'https://i.ytimg.com/an_webp/cLgJQ8Zj3AA/mqdefault_6s.webp',
          },
        },
      },
    }

    const rendered = h.render(Component, {
      props: {
        video,
      },
    })

    return {
      ...rendered,
      video,
    }
  }

  it('renders', () => expect(renderComponent().html()).toMatchSnapshot())
})
