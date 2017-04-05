import ReactDOM from 'react-dom'
import React from 'react'
import App from './src/App'
import {DateFilter} from './src/ctrl/Filters'
require('./styles/App.sass')
require('./src/lib/react-helper.js');

import {AppContainer} from 'react-hot-loader';

import jQuery from 'jquery'
if (!global.$)
    global.$ = jQuery;


ReactHelper.register('DateFilter', DateFilter);

ReactHelper.initComponents();

if (false) {
    require('./styles/App.sass')


    const root = document.getElementById('backs-form');

    const render = (Component) => {
        ReactDOM.render((
                <AppContainer>
                    <Component/>
                </AppContainer>
            ), root
        );
    }

    render(App)

    if (module.hot) {
        module.hot.accept('./src/App', () => render(App));
    }

}

if (module.hot) {
    module.hot.accept();
}