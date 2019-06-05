define(
[
    ,
    'core/react',
    'core/react-dom',
    'core/template_view_store',
    'core/mustache_component',
    'core/templates'
],
function(
    Mobx,
    React,
    ReactDOM,
    ViewStore,
    MustacheComponent,
    Templates
) {
    return {
        init: function() {
            var elements = document.querySelectorAll('[data-view-store-id]');
            elements.forEach(function(element) {
                var id = element.getAttribute('data-view-store-id');
                var templateName = element.getAttribute('data-template-name');
                var context = ViewStore.get(id);
                context = Templates.addHelpers(context);

                Templates.getTemplateSource(templateName).then(function(templateSource) {
                    ReactDOM.render(
                        React.createElement(MustacheComponent, {template: templateSource, context: context}, null),
                        element
                    );

                    var addItem = function() {
                        setTimeout(function() {
                            context.title = Date.now();
                            console.log(context);
                            addItem();
                        }, 1000)
                    }

                    //addItem();
                });
            });
        }
    };
});
