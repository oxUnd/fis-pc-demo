{%extends file="common:page/common/layout/layout.tpl"%}

{%block name="main"%}
    {%require name="photo:static/photo/index/index.css"%}
    {%require name="photo:static/photo/index/index.js"%}
<h3>demo 1</h3>
<pre>
    <code>
        &lt;script type="text/javascript"&gt;
        document.getElementById('btn').onclick = function() {
                require.async(['photo:respClick'], function(resp) {
                resp.hello();
            });
        };
        &lt;/script&gt;
    </code>
</pre>
    <button id="btn">Button</button>
    {%script type="text/javascript"%}
        document.getElementById('btn').onclick = function() {
            require.async(['photo:respClick'], function(resp) {
                resp.hello();
            });
        };
    {%/script%}
{%/block%}