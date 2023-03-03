'use strict'

module.exports = (ctx) => ({
  map: ctx.file.dirname.includes('examples') ? false : {
    inline: false,
    prev: false,
    annotation: false,
    sourcesContent: false
  },
  plugins: {
    'autoprefixer': {
      cascade: false,
    },
    'postcss-assets': {
      cachebuster: true,
      relative: true,
      loadPaths: ['images'],
      baseUrl: '/themes/cosicani_theme/'
    }
  }
})
