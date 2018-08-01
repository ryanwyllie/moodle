import React from 'core/react';
import ReactDOM from 'core/react-dom';
import ViewStore from 'core/template_view_store';
import MustacheComponent from 'core/mustache_component';
import Templates from 'core/templates';

export const init = () => {
    const elements = document.querySelectorAll('[data-view-store-id]');
    elements.forEach((element) => {
        const id = element.getAttribute('data-view-store-id');
        const templateName = element.getAttribute('data-template-name');
        let context = ViewStore.get(id);
        context = Templates.addHelpers(context);
        
        Templates.getTemplateSource(templateName).then((templateSource) => {
            ReactDOM.render(
                <MustacheComponent template={templateSource} context={context} />,
                element
            );

            const addItem = () => {
                setTimeout(() => {
                    context.title = Date.now();
                    console.log(context);
                    addItem();
                }, 1000)
            }

            //addItem();
        });
    });
}