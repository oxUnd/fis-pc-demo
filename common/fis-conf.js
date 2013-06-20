fis.config.merge({
    namespace: 'common',
    pack : {
        '/static/common/pkg.js' : [/ui\/pkg_.+.js/i, /jquery\.js/i]
    }
});