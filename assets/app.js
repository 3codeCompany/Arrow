import ReactDOM from 'react-dom'
import React from 'react'
import App from './src/App'
import {DateFilter, SelectFilter, NumericFilter, SwitchFilter,TextFilter, MultiFilter, withFilterOpenLayer} from './src/ctrl/Filters'
import {Table} from './src/ctrl/Table'
import {Button} from 'ctrl/Button'


require('./styles/App.sass')
require('./src/lib/react-helper.js');
import {AppContainer} from 'react-hot-loader';
import jQuery from 'jquery'
require('./src/lib/AjaxForm.js');
//todo in future
//require('./src/common/panel');

import alertify from './node_modules/alertifyjs/build/alertify.min'


if (!global.$)
    global.$ = jQuery;

global.alertify  = alertify;

global.ReactDOM = ReactDOM;
global.React = React;


ReactHelper.register('DateFilter', DateFilter);
ReactHelper.register('SelectFilter', SelectFilter);
ReactHelper.register('SwitchFilter', SwitchFilter);
ReactHelper.register('NumericFilter', NumericFilter);
ReactHelper.register('TextFilter', withFilterOpenLayer(TextFilter));
ReactHelper.register('MultiFilter', MultiFilter);
ReactHelper.register('Table', Table);
ReactHelper.register('Button', Button);

require ("./build/components.include");

ReactHelper.initComponents();


//import admin_reclaim_todo from '../../../../app/views/admin/reclaim/todo.component.js';
//ReactHelper.register('admin_reclaim_todo', admin_reclaim_todo);
//ReactHelper.register('admin_reclaim_todo', ArrowViews["admin_reclaim_todo"]);


if (module.hot) {
    module.hot.accept();
}