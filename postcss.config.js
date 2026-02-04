module.exports = {
  plugins: {
    cssnano: {
      preset: ['default', {
        discardComments: {
          removeAll: true
        },
        normalizeWhitespace: true,
        colormin: true,
        minifySelectors: true,
        minifyFontValues: true,
        convertValues: true,
        calc: true,
        reduceIdents: false,
        zindex: false
      }]
    }
  }
};
