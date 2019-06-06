define(
[
    'core/create-react-class',
    'core/react',
    'core/templates',
    'core/mobx',
    'core/mobx-react',
    'core/html-to-react',
    'core/react-event-handler-attributes'
],
function(
    CreateReactClass,
    React,
    Templates,
    Mobx,
    MobxReact,
    HtmlToReact,
    ReactEventHandlerAttributes
) {
    var processNodeDefinitions = new HtmlToReact.ProcessNodeDefinitions(React);
    var parser = new HtmlToReact.Parser({lowerCaseAttributeNames: false});
    var mustacheComponent = CreateReactClass({
        getProcessingInstructions: function() {
            return [
                {
                    shouldProcessNode: function(node) {
                        if (node.attribs) {
                            for (var attribute in node.attribs) {
                                if (ReactEventHandlerAttributes.isEventAttribute(attribute)) {
                                    return true;
                                }
                            }
                        }

                        return false;
                    },
                    processNode: function(node, children, index) {
                        for (var attribute in node.attribs) {
                            if (ReactEventHandlerAttributes.isEventAttribute(attribute)) {
                                var value = node.attribs[attribute];
                                var evaled = eval(value)
                                node.attribs[attribute] = evaled;
                            }
                        }

                        return processNodeDefinitions.processDefaultNode(node, children, index);
                    }.bind(this)
                },
                {
                    shouldProcessNode: function() {
                        return true;
                    },
                    processNode: processNodeDefinitions.processDefaultNode
                }
            ];
        },

        render: function() {
            var template = this.props.template;
            var context = this.props.context;
            var start = Date.now();
            // Render the template.
            var html = Templates.syncRender(template, context);
            //console.log(html);
            var mustacheRender = Date.now() - start;

            start = Date.now();
            var components = parser.parseWithInstructions(
                html,
                function() {
                    return true;
                },
                this.getProcessingInstructions()
            );
            var componentCreation = Date.now() - start;

            console.log(`Mustache render: ${mustacheRender}`);
            console.log(`Component creation: ${componentCreation}`);
            //console.log(components);

            return components;
        }
    });

    return MobxReact.observer(mustacheComponent);
});