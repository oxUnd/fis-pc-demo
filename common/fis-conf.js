fis.config.merge({
    namespace: 'common',
    pack : {
        '/static/aio.js' : ['widget/**.js', /ui\/.*\.js$/i]
    }
});
