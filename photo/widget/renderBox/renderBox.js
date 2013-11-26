require("/widget/ui/require/a_require.js");
require.async("/widget/ui/require_async/a_require_async.js");
module.exports = {
    hello: function() {
        alert('hello world!');
    }
};
