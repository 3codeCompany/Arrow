import React from 'react'
import ReactDOM from 'react-dom'
import {AppContainer} from 'react-hot-loader';

/**
 * react-helper.js
 *
 * Helper for Facebook's React UI Library. Add support for declare
 * component on DOM (similar to AngularJS Directive).
 *
 * Usage:
 * - Register a component:
 *   ReactHelper.register('MyComponent', MyComponent)
 * - Declare the DOM node:
 *   <div react-component="MyComponent" react-props="{'name':'value'}"
 * - There is no step 3!
 */



let registry = {};

window.ReactHelper = {

    initComponents: function (context = document) {

        const selector = '[react-component],[data-react-component]';
        context.querySelectorAll(selector).forEach((node) => {
            name =
                node.getAttribute('react-component') ||
                node.getAttribute('data-react-component');
            this.initComponent(name, node)

        });

    },


    register: function (name, constructor) {
        registry[name] = {_obj: constructor};
    },

    get: function (name) {
        return  registry[name]['_obj'];
    },


    initComponent: function (name, node) {

        let props = node.getAttribute('react-props') ||
            node.getAttribute('data-react-props') || null;
        if (props != null)
            props = window[props];


        if(!registry[name]){
            console.error(`'${name}' component not registred `)
            return;
        }

        let Component = registry[name]['_obj'];

        ReactDOM.render((
                <AppContainer>
                    <Component {...props} />
                </AppContainer>
            ), node
        );


    }
};
