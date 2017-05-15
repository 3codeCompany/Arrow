import React, {Component} from 'react';
import PropTypes from 'prop-types';

import {DateFilter, SelectFilter, NumericFilter, SwitchFilter, TextFilter, MultiFilter, filtersMapping, withFilterOpenLayer} from './Filters'

import {Button as MyButton} from 'ctrl/Button'

class Table extends Component {

    static propsTypes = {
        data: PropTypes.array,
        remoteURL: PropTypes.string,
        selectable: PropTypes.bool,
        onSelectionChange: PropTypes.func
    }

    static defaultProps = {
        onPage: 20,
        columns: [],
        buttons: [],
    }


    constructor(props) {

        super(props);


        this.state = {
            loading: false,
            firstLoaded: false,
            data: [],
            dataSourceError: false,
            dataSourceDebug: false,
            filters: {},
            order: {},
            onPage: this.props.onPage,
            currentPage: 1,
            countAll: 0,
            fixedLayout: false, // props.fixedLayout,
            columns: this.props.columns,
            bodyHeight: this.props.initHeight,
            allChecked: false,
            selection: []
        };

        //helpers
        this.tmpDragStartY = 0;
        this.xhrConnection = 0;

        if (window.controls === undefined)
            window.controls = {};
        window.controls[this.props.id] = () => this;


        let hashCode = function (s) {
            return s.split("").reduce(function (a, b) {
                a = ((a << 5) - a) + b.charCodeAt(0);
                return a & a
            }, 0);
        }
        this.hashCode = hashCode(this.props.controlKey + (window.CONTROLS_BASE_LOCATION != undefined ? window.CONTROLS_BASE_LOCATION : window.location.href));
    }


    componentWillMount() {

        if (window.localStorage[this.hashCode]) {
            this.state = {...this.state, ...JSON.parse(window.localStorage[this.hashCode])};
            this.state.firstLoaded = false;
        }
    }

    getDataFromChildren() {
        let loader = new MarkupLoader(this.props.children);
        this.state.columns = loader.getConfig();

    }


    componentDidUpdate() {
        let state = this.state;
        window.localStorage[this.hashCode] = JSON.stringify({
            onPage: state.onPage,
            currentPage: state.currentPage,
            bodyHeight: state.bodyHeight,
            filters: state.filters,
            order: state.order,
            fixedLayout: state.fixedLayout

        });
    }

    componentDidMount() {
        this.load();
    }

    getRequestData() {
        return {
            //need to deep clone and events remove
            columns: JSON.parse(JSON.stringify(this.state.columns)),
            filters: this.state.filters,
            order: this.state.order,
            onPage: this.state.onPage,
            currentPage: this.state.currentPage
        }
    }

    load() {
        if (this.xhrConnection) {
            this.xhrConnection.abort();
        }

        this.state.dataSourceError = false;
        //this.state.dataSourceDebug = false;
        this.setState({loading: true});
        let xhr = new XMLHttpRequest();
        xhr.onload = (e) => {
            let parsed;
            if (xhr.status === 200) {
                //parsed = {data: [], countAll: 0};
                try {
                    let parsed = JSON.parse(xhr.responseText)
                    this.setState({
                        data: parsed.data.slice(0),
                        countAll: 0 + parseInt(parsed.countAll),
                        loading: false,
                        dataSourceDebug: parsed.debug ? parsed.debug : false,
                        firstLoaded: true,
                        selection: [],
                        allChecked: false
                    });
                } catch (e) {
                    this.setState({dataSourceError: xhr.responseText})
                }
            }
        }
        xhr.open('PUT', this.props.remoteURL + '?' + new Date().getTime(), true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify(this.getRequestData()));
        this.xhrConnection = xhr;
    }

    handleStateRemove() {
        delete  window.localStorage[this.hashCode];
        if (confirm("Wyczyszczono dane tabelki, czy chcesz odświeżyć stronę?")) {
            window.location.reload();
        }
    }

    handleFilterChanged(field, value, condition, caption, labelCaptionSeparator, label) {
        this.state.filters[field] = {
            field: field,
            value: value,
            condition: condition,
            'caption': caption,
            labelCaptionSeparator: labelCaptionSeparator,
            label: label
        };
        this.setState({currentPage: 1, filters: this.state.filters}, this.load);
    }

    handleFilterDelete(key) {
        delete this.state.filters[key];
        this.setState({currentPage: 1, filters: this.state.filters}, this.load);
    }

    handleOrderDelete(field) {
        delete this.state.order[field]
        this.setState({}, this.load);
    }

    headClicked(index, e) {
        if (e.target.tagName != 'TH') {
            return;
        }

        let column = this.state.columns.filter(c => c.display === true)[index];

        if (!column.orderField)
            return;

        let field = {};

        const _field = column.field;


        if (this.state.order[_field]) {
            field = this.state.order[_field];
        } else {
            field = {
                caption: column.caption,
                field: column.orderField,
                dir: 'desc'
            }
        }

        field = {...field, dir: field.dir == "asc" ? "desc" : "asc"};

        this.state.order[_field] = field;

        this.setState({order: this.state.order}, this.load);

    }

    handleOnPageChangepage(onPage) {
        this.setState({onPage: onPage, currentPage: 1}, this.load);
    }

    handleCurrentPageChange(page) {
        let newPage = Math.max(1, Math.min(Math.ceil(this.state.countAll / this.state.onPage), page));
        if (newPage != this.state.currentPage) {
            this.setState({currentPage: newPage, selection: [], allChecked: false}, this.load);
        }
    }

    toggleFixedLayout() {
        this.setState({
            fixedLayout: !this.state.fixedLayout
        });
    }

    handleBodyResizeStart(e) {
        this.tmpDragStartY = e.clientY
        this.tmpCurrHeight = this.state.bodyHeight;
    }

    handleBodyResize(e) {
        if (e.clientY) {
            //this.setState({bodyHeight:  this.tmpCurrHeight + (-this.tmpDragStartY + e.clientY)});
        }
    }

    handleBodyResizeEnd(e) {
        this.setState({bodyHeight: this.tmpCurrHeight + (-this.tmpDragStartY + e.clientY)});
    }


    handleKeyDown(e) {
        //right
        if (e.keyCode == 39) {
            this.handleCurrentPageChange(this.state.currentPage + 1);
        }

        //left
        if (e.keyCode == 37) {
            this.handleCurrentPageChange(this.state.currentPage - 1);

        }
    }

    handleCheckClicked(index) {
        let s = this.state.selection;
        if (index == 'all') {

            if (!this.state.allChecked) {
                this.state.data.forEach((el, index) => {
                    if (s.indexOf(index) == -1) {
                        s.push(index);
                    }
                });
            } else {
                s = [];
            }
            this.setState({allChecked: !this.state.allChecked});
        } else {

            let selected = s.indexOf(index);
            if (selected == -1)
                s.push(index);
            else
                s.splice(selected, 1);

            if (s.length == this.state.data.length) {
                this.state.allChecked = true;
            } else {
                this.state.allChecked = false;
            }

        }

        if (this.props.onSelectionChange) {
            let tmp = [];
            s.forEach(index => tmp.push(this.state.data[index]));
            this.props.onSelectionChange(tmp, this);
        }

        this.setState({selection: s});

    }


    render() {

        if (this.props.children !== undefined) {
            this.getDataFromChildren();
        }
        const columns = this.state.columns;


        return (
            <div className={'w-table ' + (this.state.loading ? 'w-table-loading' : '')} ref="container" tabIndex={0} onKeyDown={this.handleKeyDown.bind(this)}>
                <div className="w-table-loader">
                    <span><i></i><i></i><i></i><i></i></span>
                </div>
                <div className="w-table-top">
                    <FiltersPresenter order={this.state.order} filters={this.state.filters}
                                      FilterDelete={this.handleFilterDelete.bind(this)}
                                      orderDelete={this.handleOrderDelete.bind(this)}
                    />

                </div>


                <table className={this.state.fixedLayout ? 'w-table-fixed' : ''}>
                    <thead>
                    <tr>
                        {this.props.selectable ?
                            <th className="w-table-selection-header" onClick={this.handleCheckClicked.bind(this, 'all')}>
                                <input type="checkbox" checked={this.state.allChecked}/>
                            </th>
                            : null
                        }
                        {columns.filter(el => el.display === true).map((el, index) => {
                            const Component = el.filter ? withFilterOpenLayer(filtersMapping[el.filter.type]) : null;
                            let classes = []
                            if (this.state.order[el.field] !== undefined) {
                                classes.push('w-table-sorted w-table-sorted-' + this.state.order[el.field].dir)
                            }
                            if (this.state.filters[el.field] !== undefined) {
                                classes.push('w-table-filtered')
                            }
                            return (
                                <th key={index} onClick={this.headClicked.bind(this, index)}
                                    style={{width: el.width}}
                                    className={classes.join(' ')}
                                >
                                    {el.order ? <i className={'fa fa-' + (el.order == 'asc' ? 'arrow-down' : 'arrow-up')}></i> : ''}
                                    {el.caption}
                                    {el.filter ? <Component onChange={this.handleFilterChanged.bind(this)} {...el.filter} caption={el.caption}/> : ''}
                                </th>)
                        })}
                    </tr>
                    </thead>


                    {this.state.dataSourceError && <Error colspan={columns.length+1} error={this.state.dataSourceError}/>}
                    {!this.state.loading && this.state.data.length + 1 == 0 && <EmptyResult colspan={columns.length+1}/>}
                    {this.state.loading && !this.state.firstLoaded && <Loading colspan={columns.length+1}/>}
                    {this.state.firstLoaded && this.state.data.length > 0 && <Rows
                        selection={this.state.selection}
                        onCheck={this.handleCheckClicked.bind(this)}
                        selectable={this.props.selectable}
                        columns={columns} filters={this.state.filters}
                        order={this.state.order} loading={this.state.loading}
                        bodyHeight={this.state.fixedLayout ? this.state.bodyHeight : 'auto'}
                        data={this.state.data}
                    />}

                    <tfoot>
                    {this.state.firstLoaded && this.state.data.length > 0 &&
                    <Footer
                        columns={columns}
                        count={this.state.countAll}
                        onPage={this.state.onPage}
                        onPageChanged={this.handleOnPageChangepage.bind(this)}
                        currentPage={this.state.currentPage}
                        currentPageChanged={this.handleCurrentPageChange.bind(this)}
                        bodyResizeStart={this.handleBodyResizeStart.bind(this)}
                        bodyResize={this.handleBodyResize.bind(this)}
                        bodyResizeEnd={this.handleBodyResizeEnd.bind(this)}
                        parent={this}
                    />}
                    </tfoot>
                </table>

                {this.state.dataSourceDebug ? <pre>{this.state.dataSourceDebug}</pre> : null}


                {/*<pre>{JSON.stringify(this.state.columns, null, 2)}</pre>
                 <pre>{JSON.stringify(this.state.order, null, 2)}</pre>
                 <pre>
                 image
                 link
                 template
                 menu
                 </pre>*/}

            </div>
        )
    }

}


function FiltersPresenter(props) {

    let isVisible = Object.entries(props.order).length > 0 || Object.entries(props.filters).length > 0;
    return (
        <div className={'w-table-presenter ' + (!isVisible ? 'w-table-presenter-hidden' : '')}>
            <div className="w-table-presenter-inner">
                {Object.entries(props.order).map(([field, el], index) =>
                    <div key={index}>
                        <div><i className={'fa fa-' + (el.dir == 'asc' ? 'arrow-down' : 'arrow-up')}></i></div>
                        <div className="caption">{el.caption}</div>
                        <div className="remove" onClick={(e) => props.orderDelete(field)}><i className="fa fa-times"></i></div>
                    </div>
                )}

                {Object.entries(props.filters).map(([key, el]) =>
                    <div key={key}>
                        <div><i className="fa fa-filter"></i></div>
                        <div className="caption">{el.caption}</div>
                        <div className="value" dangerouslySetInnerHTML={{__html: el.label}}/>
                        <div className="remove" onClick={(e) => props.FilterDelete(key)}><i className="fa fa-times"></i></div>
                    </div>
                )}
            </div>
        </div>
    )
}


function Loading(props) {
    return (
        <tbody>
        <tr>
            <td className="w-table-center" colSpan={props.colspan}>
                {/*<i className="fa fa-spinner fa-spin"></i>*/}
            </td>
        </tr>
        </tbody>
    )
}
function EmptyResult(props) {
    return (
        <tbody>
        <tr>
            <td className="w-table-center" colSpan={props.colspan}><h4 >Brak danych</h4></td>
        </tr>
        </tbody>
    )
}


function Error(props) {
    return (
        <tbody>
        <tr>
            <td colSpan={props.colspan}>
                <span dangerouslySetInnerHTML={{__html: props.error}}/>
            </td>
        </tr>
        </tbody>
    )
}

function Footer(props) {
    const pages = Math.max(Math.ceil(props.count / props.onPage), 1);

    const leftRightCount = 0;

    const from = Math.max(1, Math.min(pages - leftRightCount * 2, Math.max(1, props.currentPage - leftRightCount)));
    var arr = (function (a, b) {
        while (a--)b[a] = a + from;
        return b
    })(Math.min(leftRightCount * 2 + 1, pages > 0 ? pages : 1), []);
    const table = props.parent;

    return (
        <tr>
            <td colSpan={props.columns.length + 1} className="w-table-footer-main">


                <div className="w-table-pager">
                    <div onClick={(e) => props.currentPageChanged(1)}><i className="fa fa-angle-double-left"></i></div>
                    <div onClick={(e) => props.currentPageChanged(Math.max(1, props.currentPage - 1))}><i className="fa fa-angle-left"></i></div>
                    {arr.map((el, i) =>
                        <div key={i} onClick={(e) => props.currentPageChanged(el)} className={el == props.currentPage ? 'w-table-pager-active' : ''}>{el}</div>
                    )}
                    <div onClick={(e) => props.currentPageChanged(Math.min(props.currentPage + 1, pages))}><i className="fa fa-angle-right"></i></div>
                    <div onClick={(e) => props.currentPageChanged(pages)}><i className="fa fa-angle-double-right"></i></div>
                </div>

                <div className="w-table-footer-pageinfo">
                    {props.currentPage} / {pages} [ {props.count} ]
                </div>

                <div className="w-table-footer-onpage-select">
                    <span>Na stronie: </span>
                    <select defaultValue={props.onPage} onChange={(e) => props.onPageChanged(parseInt(e.target.value))}>
                        {([10, 25, 50, 100, 500]).map((x, i) =>
                            <option key={'onpageval' + x} value={x}>{x}</option>
                        )}
                    </select>
                </div>


                <div className="w-table-buttons">


                    {table.props.buttons.map((e) =>
                        <MyButton {...e} context={table}/>
                    )}

                    <button title="Usuń zmiany" onClick={table.handleStateRemove.bind(table)}><i className="fa fa-eraser"></i></button>
                    <button title="Odśwież" onClick={table.load.bind(table)}><i className="fa fa-refresh"></i></button>
                    {/*<button title="Zmień sposób wyświetlania" onClick={table.toggleFixedLayout.bind(table)}><i className="fa fa-window-restore"></i></button>*/}
                    <div
                        title="Przesuń i upuść aby zmienić rozmiar tabeli"
                        className="w-table-footer-drag"
                        onDragStart={(e) => {
                            props.bodyResizeStart(e)
                        }} onDrag={(e) => {
                        props.bodyResize(e)
                    }}
                        onDragEnd={(e) => {
                            props.bodyResizeEnd(e)
                        }}
                        draggable={true}
                    ><i className="fa fa-arrows-v"></i></div>
                    {/*{table.state.loading ? <button className="w-table-loading-indicator"><i className="fa fa-spinner fa-spin"></i></button> : ''}*/}
                </div>


            </td>
        </tr>
    )
}

function Rows(props) {
    const cells = {
        'Simple': ColumnSimple,
        'Map': ColumnMap,
        'Date': ColumnDate,
        'Multi': ColumnMulti,
    };

    const packValue = (val, props) => {
        let templateResult = false;
        if (props.column.template) {
            templateResult = props.column.template(val, props.row);

        }
        return (
            <div>
                {props.column.icon ? <i className={'w-table-prepend-icon fa ' + props.column.icon}></i> : ''}
                {props.column.prepend ? props.column.prepend : ''}
                {templateResult ? (typeof templateResult == "string" ? <span dangerouslySetInnerHTML={{__html: (props.column.template(val, props.row))}}></span> : templateResult) : (val ? val : props.column.default)}
                {props.column.append ? props.column.append : ''}
            </div>
        )
    };

    const columns = props.columns.filter(el => el.display === true);

    let cache = {};

    for (let index = 0; index < columns.length; index++) {
        let column = columns[index];
        cache[index] = cache[index] || {}
        cache[index].classes = [];
        if (props.order[column.field] !== undefined) {
            cache[index].classes.push('w-table-sorted w-table-sorted-' + props.order[column.field].dir);
        }
        if (props.filters[column.field] !== undefined) {
            cache[index].classes.push('w-table-filtered')
        }
        if (column.events.click && column.events.click.length > 0) {
            cache[index].classes.push('w-table-cell-clickable');
        }
        cache[index].classes = cache[index].classes.concat(column.class);
    }


    return (


        <tbody style={{maxHeight: props.bodyHeight}}>


        {props.data.map((row, index) =>
            <tr key={'row' + index}>
                {props.selectable ?
                    <td className="w-table-selection-cell" onClick={(e) => props.onCheck(index, e)}>
                        <input type="checkbox" checked={props.selection.indexOf(index) != -1}/>
                    </td>
                    : ''}
                {columns.map((column, index2) => {
                    const Component = column.type ? cells[column.type] : cells['Simple'];
                    return (<td key={'cell' + index2}
                                style={{width: column.width}}
                                onClick={column.events.click ? (event) => {

                                    column.events.click.map((callback) => {
                                        callback.bind(this)(row, event);
                                    })
                                } : function () {
                                }}
                                onMouseEnter={column.events.enter ? (event) => {
                                    column.events.enter.map((callback) => {
                                        callback.bind(this)(row, event);
                                    })
                                } : function () {
                                }}
                                onMouseLeave={column.events.leave ? (event) => {
                                    column.events.leave.map((callback) => {
                                        callback.bind(this)(row, event);
                                    })
                                } : function () {
                                }}
                                className={cache[index2].classes.concat(column.classDecorator[row[column.field]]).join(' ')}
                        >
                            <Component column={column} row={row} cells={cells} packValue={packValue}/>
                        </td>
                    )
                })}
            </tr>
        )}

        </tbody>
    )
}

function ColumnSimple(props) {
    return (
        <div>
            {props.packValue(props.column.field ? (props.row[props.column.field] ? props.row[props.column.field] : props.column.default) : '', props)}

        </div>
    )
}

function ColumnDate(props) {
    return (
        <div className="w-table-cell-date">
            {props.packValue(props.column.field ? (props.row[props.column.field] ? props.row[props.column.field] : props.column.default) : '', props)}
        </div>
    )
}


function ColumnMap(props) {
    const value = props.row[props.column.field];
    return (
        <div>
            {props.packValue(props.column.map[value] ? props.column.map[value] : value, props)}
        </div>
    )
}

function ColumnMulti(props) {
    return (
        <div>
            {/*{JSON.stringify(props.column.columns)}*/}

            {props.column.columns.map((column) => {
                const Component = column.type ? props.cells[column.type] : props.cells["Simple"];
                let classes = ['w-table-cell-multi']
                if (column.classDecorator[props.row[column.field]])
                    classes.push(column.classDecorator[props.row[column.field]]);
                if (column.classDecorator[props.row[column.field]])
                    classes.push(column.classDecorator[props.row[column.field]]);
                if (column.class)
                    classes = classes.concat(column.class);

                return (<div key={'multi' + column.field} className={classes.join(' ')}>
                    <Component column={column} row={props.row} packValue={props.packValue}/>
                </div>)
            })}

        </div>
    )
}


const defaultColumnData = {
    field: "",
    caption: "",
    isSortable: true,
    display: true,
    toolTip: null,
    width: null,
    class: [],
    type: "Simple",
    orderField: "id",
    icon: null,
    append: null,
    prepend: null,
    classDecorator: [],
    template: null,
    default: ""
};

class MarkupLoader {
    constructor(columns) {
        this.config = [];
        //this.firstLevel = firstLevel;
        columns = Array.isArray(columns) ? columns : [columns];
        columns.map(column => {


            if (column.type.name == "Column") {
                let data = this.getPropsAsData(column.props);
                data.orderField = data.orderField || data.field;
                data.type = data.type || "Simple";
                data.isSortable = data.isSortable || true;
                data.default = data.default || "";
                data.events = {
                    click: [], enter: [], leave: []
                };
                if (data.onClick) {
                    data.events.click.push(data.onClick);
                    delete data.onClick;
                }
                if (data.onMouseEnter) {
                    data.events.enter.push(data.onMouseEnter);
                    delete data.onMouseEnter;
                }
                if (data.onMouseLeave) {
                    data.events.leave.push(data.onMouseLeave);
                    delete data.onMouseLeave;
                }

                if (data.className) {
                    data.class = [data.className];
                    delete data.className;
                }

                let config = {...defaultColumnData, ...data};

                let filters = [];
                let sorters = [];
                if (column.props.children !== undefined) {
                    let children = column.props.children;
                    if (!Array.isArray(children))
                        children = [children];


                    children.map((child) => {
                        if (child.type.name == "Filter") {
                            filters.push(this.getFilterConf(this.getPropsAsData(child.props), data))
                        }
                        if (child.type.name == "Sorter") {
                            sorters.push(this.getSorterConf(this.getPropsAsData(child.props), data));
                        }

                    });
                }

                if (filters.length == 0 && data.noDefaultFilter === undefined) {
                    config.filter = this.getColumnDefaultFilter(data);
                } else if (filters.length == 1) {
                    config.filter = filters[0];
                } else if (filters.length > 1) {
                    config.filter = {
                        "type": "MultiFilter",
                        "field": "id",
                        "title": "Id",
                        "caption": "Id",
                        filters: filters
                    }
                }


                this.config.push(config);
            }
        });
    }

    getFilterConf(data, column) {
        return {
            ...data, ...{
                type: data.type[0].toUpperCase() + data.type.slice(1) + "Filter",
                field: data.field || column.field,
                title: data.caption || column.caption,
                caption: data.caption || column.caption,

            }
        }
    }

    getColumnDefaultFilter(column) {
        return {
            type: "TextFilter",
            field: column.field,
            title: column.caption,
            caption: column.caption,
        }
    }

    getPropsAsData(props) {
        let data = {};
        for (let i in props) {
            if (i != "children") {
                data[i] = props[i];
            }
        }
        return data;
    }

    getConfig() {
        return this.config;
    }

}
const Column = () => null
const Filter = () => null
const Sorter = () => null


export {Table, Column, Filter, Sorter}