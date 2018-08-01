import React from 'core/react';
import Mustache from 'core/mustache';
import Templates from 'core/templates';
import h from 'core/react-hyperscript';
import html2hyperscript from 'core/html2hyperscript';
import { observer } from "core/mobx-react";
import { Parser, ProcessNodeDefinitions } from 'core/html-to-react';
import { isEventAttribute } from 'core/react-event-handler-attributes';

const processNodeDefinitions = new ProcessNodeDefinitions(React);
const classFromString = new Function('className', 'return className');

class MustacheComponent extends React.Component {
    getProcessingInstructions(context) {
        const thing = this;
        const foo = function(node, children, index) {
            for (const attribute in node.attribs) {
                if (isEventAttribute(attribute)) {
                    const value = node.attribs[attribute];
                    const evaled = eval(value);
                    node.attribs[attribute] = evaled;
                }
            }

            return processNodeDefinitions.processDefaultNode(node, children, index);
        };

        return [
            {
                replaceChildren: false,
                shouldProcessNode: node => {
                    return node.attribs && node.attribs['react-component'];
                },
                processNode: (node, children, index) => {
                    const component = eval(node.attribs['react-component']);
                    const attributes = {
                        key: index,
                        context,
                        ...node.attribs
                    };
        
                    return React.createElement(component, attributes);
                }
            },
            {
                shouldProcessNode: node => {
                    if (node.attribs) {
                        for (const attribute in node.attribs) {
                            if (isEventAttribute(attribute)) {
                                return true;
                            }
                        }
                    }

                    return false;
                },
                processNode: foo.bind(thing)
            },
            {
                shouldProcessNode: node => true,
                processNode: processNodeDefinitions.processDefaultNode
            }
        ]
    }

    render() {
        const { template, context } = this.props;

        let start = Date.now();
        // Render the template.
        const html = Templates.syncRender(template, context);
        const mustacheRender = Date.now() - start;

        start = Date.now();
        const parser = new Parser({lowerCaseAttributeNames: false});
        const components = parser.parseWithInstructions(
            html,
            () => true,
            this.getProcessingInstructions(context)
        );
        const componentCreation = Date.now() - start;

        console.log(`Mustache render: ${mustacheRender}`);
        console.log(`Component creation: ${componentCreation}`);

        return components;

        /*
        // Parse the rendered HTML into hyperscript syntax.
        start = Date.now();
        const hyperscript = html2hyperscript(html);
        const hyperscriptRender = Date.now() - start;
        // Create React components from the hyperscript syntax.
        start = Date.now();
        const createComponents = new Function('h', `return h('div', [${hyperscript}]);`);
        // Give the function the hyperscript implementation the creates
        // React elements instead of regular DOM elements.
        const components = createComponents(h);
        const componentCreation = Date.now() - start;

        console.log(`Mustache render: ${mustacheRender}`);
        console.log(`Hyperscript render: ${hyperscriptRender}`);
        console.log(`Component creation: ${componentCreation}`);

        return components;
        */
    }
};

export default observer(MustacheComponent);