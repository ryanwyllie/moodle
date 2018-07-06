define(
[
    'core/virtual-html',
    'core/virtual-dom',
    'core/vdom-virtualise',
    'core/mustache',
    'core/mobx'
],
function(
    VirtualHTML,
    VirtualDOM,
    VDOMVirtualise,
    Mustache,
    MobX
) {

    function init(root, input) {
        var template = '<ul>{{#items}}<li>{{.}}</li>{{/items}}</ul>';
        var context = MobX.observable({
            items: []
        });
        
        var tree = VDOMVirtualise.virtualise(root);
        var disposer = MobX.autorun(function() {
            var html = Mustache.render(template, context);
            var newTree = VirtualHTML.virtualHTML(html);
            var patches = VirtualDOM.diff(tree, newTree);

            root = VirtualDOM.patch(root, patches);
            tree = newTree;
        });

        input.on('keyup', function(e) {
            if (e.keyCode == 13) {
                context.items.push(input.val());
                input.val('');
            }
        });
    }

    return {
        init: init
    }
});