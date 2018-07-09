define(
[
    'core/virtual-html',
    'core/virtual-dom',
    'core/vdom-virtualise',
    'core/templates',
    'core/mobx'
],
function(
    VirtualHTML,
    VirtualDOM,
    VDOMVirtualise,
    Templates,
    MobX
) {

    var MustacheComponent = function(rootElement, templateName, templateContext) {
        this.hasMounted = false;
        this.rootElement = rootElement;
        this.templateName = templateName;
        this.state = MobX.observable(templateContext);
        // Construct the initial tree view.
        this.tree = VDOMVirtualise.virtualise(rootElement);
        // Run the render function any time the context changes.
        MobX.autorun(this.render.bind(this));
    };

    MustacheComponent.prototype.getTemplateContext = function() {
        return this.state;
    };

    MustacheComponent.prototype.registerEventListeners = function() {
        return;
    };

    MustacheComponent.prototype.render = function() {
        return Templates.render(
            this.templateName,
            // Copy context to trigger the MobX observability.
            MobX.toJS(this.getTemplateContext())
        ).then(function(html) {
            var newTree = VirtualHTML.virtualHTML(html);
            // Generate the diff based on our current view of the
            // DOM and the newly generated view.
            var patches = VirtualDOM.diff(this.tree, newTree);
            // Apply the patches.
            VirtualDOM.patch(this.rootElement, patches);
            // Remember the new view.
            this.tree = newTree;
        }.bind(this))
        .then(function() {
            if (!this.hasMounted) {
                this.registerEventListeners();
                this.hasMounted = true;
            }
        }.bind(this));  
    };

    return MustacheComponent;
});