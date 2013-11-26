fis.config.merge({
    namespace: 'photo',
    modules: {
        spriter: 'csssprites'
    },
    pack: {
        '/static/photo/test.js': [
            /a_require\.js/,
            /a_require_aysnc\.js/,
            /renderBox.js/
        ],
    },
    settings: {
        smarty: {
            left_delimiter: '{%',
            right_delimiter: '%}',
        },
        spriter: {
            csssprites: {
                margin: 30
            }
        }
    }
});

fis.config.get('roadmap.path').unshift( {
    reg : /^\/widget\/(.*\.(js|css))$/i,
    isMod : true,
    useSprite: true,
    release : '/static/${namespace}/widget/$1'
});
