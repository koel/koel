const path = require('path')

const ALIASES = {
  '@css': path.resolve(__dirname, 'resources/assets/css'),
}

const referenceAlias = () => ({
  postcssPlugin: 'tailwind-reference-alias',
  Once (root) {
    root.walkAtRules('reference', (rule) => {
      const raw = rule.params.trim().replace(/^['"]|['"]$/g, '')
      for (const [alias, target] of Object.entries(ALIASES)) {
        if (raw === alias || raw.startsWith(alias + '/')) {
          rule.params = `'${target}${raw.slice(alias.length)}'`
          return
        }
      }
    })
  },
})
referenceAlias.postcss = true

module.exports = {
  plugins: [
    referenceAlias(),
    require('@tailwindcss/postcss'),
    require('postcss-mixins'),
    require('postcss-nested'),
  ],
}
