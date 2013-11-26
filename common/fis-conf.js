fis.config.merge({
    namespace: 'common',
    pack : {
        '/static/common/pkg.js' : [/ui\/pkg_\d+\.js/i]
    },
    deploy: {
        test: {
            receiver: "http://zhangyunlong.fe.baidu.com/receiver.php",
            to: "/home/zhangyunlong/public_html"
        }
    }
});

var paths = fis.config.get('roadmap.path');
