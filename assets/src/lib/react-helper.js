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



var domReady = false, registry = {}, queue = [];

window.ReactHelper = {

    initComponents: function () {
        for (var i = 0; i < queue.length; i += 1) {
            this.initComponent(queue[i])
        }
    },

    forceInitComponents: function () {
        queue = queue.concat(Object.keys(registry));
        this.initComponents();
    },

    register: function (name, constructor) {
        registry[name] = {_obj: constructor};
        queue.push(name);


    },

    render: function (Component, node) {
        ReactDOM.render((
                <AppContainer>
                    <Component/>
                </AppContainer>
            ), node
        );
    },

    initComponent: function (name) {
        var props, selector =
            '[react-component="' + name + '"],[data-react-component="' + name + '"]';
        document.querySelectorAll(selector).forEach(function (node) {

            props = node.getAttribute('react-props') ||
                node.getAttribute('data-react-props') || null;
            if (props != null)
                props = JSON.parse(atob(props));



            let Component = registry[name]['_obj'];

            ReactDOM.render((
                    <AppContainer>
                        <Component {...props} />
                    </AppContainer>
                ), node
            );

        });


    }
};

window.addEventListener('DOMContentLoaded', function () {
    domReady = true;
    ReactHelper.initComponents();
});



