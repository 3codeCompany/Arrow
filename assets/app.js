import ReactDOM from 'react-dom'
import React from 'react'
import App from './src/App'
import {DateFilter, SelectFilter, NumericFilter, SwitchFilter,TextFilter, MultiFilter} from './src/ctrl/Filters'
import {Table} from './src/ctrl/Table'
require('./styles/App.sass')
require('./src/lib/react-helper.js');

import {AppContainer} from 'react-hot-loader';

import jQuery from 'jquery'
if (!global.$)
    global.$ = jQuery;


ReactHelper.register('DateFilter', DateFilter);
ReactHelper.register('SelectFilter', SelectFilter);
ReactHelper.register('SwitchFilter', SwitchFilter);
ReactHelper.register('NumericFilter', NumericFilter);
ReactHelper.register('TextFilter', TextFilter);
ReactHelper.register('MultiFilter', MultiFilter);
ReactHelper.register('Table', Table);

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