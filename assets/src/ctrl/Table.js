import React, {Component} from 'react';


import {DateFilter, SelectFilter, NumericFilter, SwitchFilter, TextFilter, MultiFilter, filtersMapping, withFilterOpenLayer} from './Filters'

import {Button as MyButton} from '../ctrl/Button'

class Table extends Component {

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
            fixedLayout: props.fixedLayout,
            columns: this.props.columns,
            bodyHeight: this.props.initHeight
        };

        //helpers
        this.tmpDragStartY = 0;
        this.xhrConnection = 0;
    }

    componentWillMount() {
        if (window.localStorage[this.props.controlKey]) {
            this.state = JSON.parse(...this.state, window.localStorage[this.props.controlKey]);
            this.state.firstLoaded = false;
        }

    }


    componentDidUpdate() {
        window.localStorage[this.props.controlKey] = JSON.stringify({...this.state, data: []});
    }

    componentDidMount() {
        this.load();
        this.refs.container.focus();

        /*let handleDragStart = (e) => {
         e.target.style.opacity = '0.4';

         }*/

        // console.log(this.refs.bodyResizeHandler);
        //this.refs.bodyResizeHandler.addEventListener('dragstart', handleDragStart, false);


    }

    getRequestData(){
        return {
            columns: this.props.columns,
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
                        firstLoaded: true
                    });
                } catch (e) {
                    this.setState({dataSourceError: xhr.responseText})
                }
            }
        }
        xhr.open('PUT', this.props.url + '?' + new Date().getTime(), true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify(this.getRequestData()));
        this.xhrConnection = xhr;
    }

    handleStateRemove() {
        delete window.localStorage['list'];
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

        if (!this.props.columns[index].orderField)
            return;

        let field = {};
        const _field = this.props.columns[index].field;

        if (this.state.order[_field]) {
            field = this.state.order[_field];
        } else {
            field = {
                caption: this.props.columns[index].caption,
                field: this.props.columns[index].orderField,
                dir: 'desc'
            }
        }

        field = {...field, dir: field.dir == "asc" ? "desc" : "asc"};

        this.state.order[_field] = field;
        this.setState({}, this.load);

    }

    handleOnPageChangepage(onPage) {
        this.setState({onPage: onPage, currentPage: 1}, this.load);
    }

    handleCurrentPageChange(page) {
        let newPage = Math.max(1, Math.min(Math.ceil(this.state.countAll / this.state.onPage), page));
        if (newPage != this.state.currentPage) {
            this.setState({currentPage: newPage}, this.load);
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

/*    transformInput(columns) {
        let x;
        columns.map(el => {
            if (el.template && typeof el.template != 'function') {
                if (el.template.indexOf('return') == -1)
                    el.template = eval('x = function(value, row){ return `' + el.template + '`; };')
                else
                    el.template = eval('x = function(value, row){ ' + el.template + ' };')
            }


            if (typeof(el.events) == 'object') {
                Object.entries(el.events).map(([_key, val]) => {
                    if (typeof el.events[_key] != 'function')
                        el.events[_key] = eval('x = function(row, event){ ' + val + '; return false; };');
                });
            }
            if (el.columns) {
                this.transformInput(el.columns);
            }
        });
    }*/

    render() {

        //this.transformInput(this.props.columns);

        const columns = this.props.columns;
        //console.dir(columns);


        return (
            <div className={'w-table ' + (this.state.loading ? 'w-table-loading' : '')} ref="container" tabIndex={0} onKeyDown={this.handleKeyDown.bind(this)}>

                <div className="w-table-top">
                    <FiltersPresenter order={this.state.order} filters={this.state.filters}
                                      FilterDelete={this.handleFilterDelete.bind(this)}
                                      orderDelete={this.handleOrderDelete.bind(this)}
                    />
                    <div className="w-table-buttons">
                        {this.state.loading ? <button className="w-table-loading-indicator"><i className="fa fa-spinner fa-spin"></i></button> : ''}

                        {this.props.buttons.map((e)=>
                            <MyButton {...e} context={this} />
                        )}

                        <button title="Usuń zmiany" onClick={this.handleStateRemove.bind(this)}><i className="fa fa-eraser"></i></button>
                        <button title="Odśwież" onClick={this.load.bind(this)}><i className="fa fa-refresh"></i></button>
                        <button title="Zmień sposób wyświetlania" onClick={this.toggleFixedLayout.bind(this)}><i className="fa fa-window-restore"></i></button>
                    </div>
                </div>


                <table className={this.state.fixedLayout ? 'w-table-fixed' : ''}>
                    <thead>
                    <tr>
                        {columns.map((el, index) => {
                            const Component = el.filter ? withFilterOpenLayer(filtersMapping[el.filter.type]) : null;
                            return (
                                <th key={index} onClick={this.headClicked.bind(this, index)}
                                    style={{width: el.width}}
                                >
                                    {el.order ? <i className={'fa fa-' + (el.order == 'asc' ? 'arrow-down' : 'arrow-up')}></i> : ''}
                                    {el.caption}
                                    {el.filter ? <Component onChange={this.handleFilterChanged.bind(this)} {...el.filter} caption={el.caption}/> : ''}
                                </th>)
                        })}
                    </tr>
                    </thead>


                    {this.state.dataSourceError && <Error colspan={columns.length} error={this.state.dataSourceError}/>}
                    {!this.state.loading && this.state.data.length == 0 && <EmptyResult colspan={columns.length}/>}
                    {this.state.loading && !this.state.firstLoaded && <Loading colspan={columns.length}/>}
                    {this.state.firstLoaded && this.state.data.length > 0 && <Rows columns={columns} loading={this.state.loading} bodyHeight={this.state.fixedLayout ? this.state.bodyHeight : 'auto'} data={this.state.data}/>}

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
                    />}
                    </tfoot>
                </table>
                {this.state.dataSourceDebug ? <pre>{this.state.dataSourceDebug}</pre> : null}


                {/*<pre>{JSON.stringify(this.props.columns, null, 2)}</pre>
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
    {/*.sort((a, b) => a.prioryty > b.prioryty)*/
    }
    return (
        <div className="w-table-presenter" style={{minHeight: '30px'}}>
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
    )
}


function Loading(props) {
    return (
        <tbody>
        <tr>
            <td className="w-table-center" colSpan={props.colspan}>
                <i className="fa fa-spinner fa-spin"></i>
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

    const leftRightCount = 2;

    const from = Math.max(1, Math.min(pages - leftRightCount * 2, Math.max(1, props.currentPage - leftRightCount)));
    var arr = (function (a, b) {
        while (a--)b[a] = a + from;
        return b
    })(Math.min(leftRightCount * 2 + 1, pages > 0 ? pages : 1), []);

    return (
        <tr>
            <td colSpan={props.columns.length} className="w-table-footer-main">
                <div className="w-table-footer-all">
                    Wszystkich <span>{props.count}</span>
                </div>
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

                <div className="w-table-pager">
                    <div onClick={(e) => props.currentPageChanged(1)}><i className="fa fa-angle-double-left"></i></div>
                    <div onClick={(e) => props.currentPageChanged(Math.max(1, props.currentPage - 1))}><i className="fa fa-angle-left"></i></div>
                    {arr.map((el, i) =>
                        <div key={i} onClick={(e) => props.currentPageChanged(el)} className={el == props.currentPage ? 'w-table-pager-active' : ''}>{el}</div>
                    )}
                    <div onClick={(e) => props.currentPageChanged(Math.min(props.currentPage + 1, pages))}><i className="fa fa-angle-right"></i></div>
                    <div onClick={(e) => props.currentPageChanged(pages)}><i className="fa fa-angle-double-right"></i></div>
                </div>

                <div className="w-table-footer-pageinfo"> strona: <b>{props.currentPage}</b> z <b>{pages}</b>
                </div>

                <div className="w-table-footer-onpage-select">
                    <span>Na stronie: </span>
                    <select defaultValue={props.onPage} onChange={(e) => props.onPageChanged(parseInt(e.target.value))}>
                        {([25, 50, 100]).map((x, i) =>
                            <option key={'onpageval' + x} value={x}>{x}</option>
                        )}
                    </select>
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

    const packalue = (val, props) => {
        return (
            <div>
                {props.column.icon ? <i className={'w-table-prepend-icon fa ' + props.column.icon}></i> : ''}
                {props.column.prepend ? props.column.prepend : ''}
                {props.column.template ? <span dangerouslySetInnerHTML={{__html: (props.column.template(val, props.row))}}></span> : (val ? val : props.column.default)}
                {props.column.append ? props.column.append : ''}
            </div>
        )
    };


    return (

        <tbody style={{maxHeight: props.bodyHeight}}>


        {props.data.map((row, index) =>
            <tr key={'row' + index}>
                {props.columns.map((column, index2) => {
                    const Component = column.type ? cells[column.type] : cells["Simple"];
                    return (<td key={'cell' + index2}
                                style={{width: column.width}}
                                onClick={column.events.click ? function (event) {
                                    column.events.click.map((callback)=> {
                                        callback.bind(this)(row,event);
                                    })
                                } : function () {
                                }}
                                className={'' + (column.events.click ? 'w-table-cell-clickable ' : '') + (column.class ? ' ' + column.class.join(' ') : '') +
                                (column.classDecorator[row[column.field]] ? column.classDecorator[row[column.field]] + " " : '')
                                }
                        >
                            <Component column={column} row={row} cells={cells} packValue={packalue}/>
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

export {Table}