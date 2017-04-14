import React, {Component} from 'react';

import {DateFilter, SelectFilter, NumericFilter, SwitchFilter, TextFilter} from './Filters'


class Table extends Component {

    constructor(props) {

        super(props);

        this.state = {
            loaded: false,
            data: [],
            dataSourceError: false,
            filters: {},
            onPage: this.props.onPage,
            currentPage: 1,
            countAll: 0,
            fixedLayout: props.fixedLayout
        };

        if(window.localStorage['list']){
            this.state = JSON.parse(window.localStorage['list']);
        }

    }



    componentDidMount() {
        this.load();
    }

    componentDidUpdate(){
        window.localStorage['list'] = JSON.stringify(this.state);
    }

    load() {

        this.state.dataSourceError = false;
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
                        loaded: true
                    });
                } catch (e) {
                    this.setState({dataSourceError: xhr.responseText})
                }
            }
        }
        xhr.open('PUT', this.props.url + '?' + new Date().getTime(), true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.send(JSON.stringify({
            columns: this.props.columns,
            filters: this.state.filters,
            onPage: this.state.onPage,
            currentPage: this.state.currentPage
        }));
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

    handleOnSortChange(sort) {
        this.props.columns.map(el => {
            if (el.field == sort) {
                delete el['order'];
                delete el['orderPrioryty'];
            }
        });
        this.setState({}, this.load);
    }

    headClicked(index, e) {
        if (e.target.tagName != 'TH') {
            return;
        }


        let currrent = this.props.columns.filter((el) => {
            return el.order
        });

        let prioryty = 1;
        if (currrent.length)
            prioryty = currrent.reduce((pv, cv) => pv + cv.orderPrioryty, 0);


        if (this.props.columns[index].order) {
            if (this.props.columns[index].order == 'asc') {
                this.props.columns[index].order = 'desc';
                this.props.columns[index].orderPrioryty = prioryty;
            } else {
                this.props.columns[index].order = false;
                this.props.columns[index].orderPrioryty = 0;
            }
        } else {
            this.props.columns[index].order = 'asc';
            this.props.columns[index].orderPrioryty = prioryty;
        }
        this.setState({});
        this.load();
    }

    handleOnPageChangepage(onPage) {
        this.setState({onPage: onPage}, this.load);
    }

    handleCurrentPageChange(page) {
        this.setState({currentPage: page}, this.load);
    }

    toggleFixedLayout() {
        this.setState({
            fixedLayout: !this.state.fixedLayout
        });
    }


    transformInput() {
        let x;
        this.props.columns.map(el => {
            if (el.template && typeof el.template != 'function') {
                el.template = eval('x = function(row){ return `' + el.template + '`; };')
            }

            if (typeof(el.events) == 'object') {
                Object.entries(el.events).map(([_key, val]) => {
                    if (typeof el.events[_key] != 'function')
                        el.events[_key] = eval('x = function(row){ ' + val + '; return false; };');
                });
            }
        });
    }

    render() {

        this.transformInput();

        const columns = this.props.columns;
        //console.dir(columns);

        const filters = {
            'NumericFilter': NumericFilter,
            'DateFilter': DateFilter,
            'SelectFilter': SelectFilter,
            'SwitchFilter': SwitchFilter,
            'TextFilter': TextFilter
        };

        return (
            <div className="w-table">
                <h4>React table {this.props.id}</h4>
                <div className="w-table-top">

                    <FiltersPresenter columns={columns} filters={this.state.filters}
                                      FilterDelete={this.handleFilterDelete.bind(this)}
                                      onSortChange={this.handleOnSortChange.bind(this)}
                    />
                    <div className="w-table-buttons">
                        <button onClick={this.toggleFixedLayout.bind(this)}><i className="fa fa-window-restore"></i></button>
                    </div>
                </div>


                <table className={this.state.fixedLayout ? 'w-table-fixed' : ''}>
                    <thead>
                    <tr>
                        {columns.map((el, index) => {
                            const Component = el.filter ? filters[el.filter.type] : null;
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

                    {!this.state.loaded && <Loading colspan={columns.length}/>}
                    {this.state.dataSourceError && <Error colspan={columns.length} error={this.state.dataSourceError}/>}
                    {this.state.loaded && this.state.data.length == 0 && <EmptyResult colspan={columns.length}/>}

                    {this.state.loaded && this.state.data.length > 0 && <Rows columns={columns} data={this.state.data}/>}

                    <tfoot>
                    {this.state.loaded && this.state.data.length > 0 &&
                    <Footer
                        columns={columns}
                        count={this.state.countAll}
                        onPage={this.state.onPage}
                        onPageChanged={this.handleOnPageChangepage.bind(this)}
                        currentPage={this.state.currentPage}
                        currentPageChanged={this.handleCurrentPageChange.bind(this)}
                    />}
                    </tfoot>
                </table>
                <br /><br />

                <pre>{JSON.stringify(this.state.filters, null, 2)}</pre>
                <pre>{JSON.stringify(this.props, null, 2)}</pre>
                <pre>
                    image
                    link
                    template
                    menu
                </pre>
            </div>
        )
    }

}

function FiltersPresenter(props) {
    return (
        <div className="w-table-presenter" style={{minHeight: '30px'}}>
            {props.columns.filter((el) => {
                return el.order
            }).sort((a, b) => a.orderPrioryty > b.orderPrioryty)
                .map((el, index) =>
                    <div>
                        <div><i className={'fa fa-' + (el.order == 'asc' ? 'arrow-down' : 'arrow-up')}></i></div>
                        <div className="caption">{el.caption}</div>
                        <div className="remove" onClick={(e) => props.onSortChange(el.field)}><i className="fa fa-times"></i></div>
                    </div>
                )}

            {Object.entries(props.filters).map(([key, el]) =>
                <div>
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
function Rows(props) {
    return (
        <tbody>
        {props.data.map((row, index) =>
            <tr key={'row' + index}>
                {props.columns.map((column, index2) =>
                    <td key={'cell' + index2}
                        style={{width: column.width}}
                        onClick={column.events.click ? function () {
                            column.events.click(row);
                        } : function () {
                        }}
                        className={'' + (column.events.click ? 'w-table-cell-clickable' : '') + (column.class ? ' ' + column.class.join(' ') : '')}
                    >
                        {column.field && !column.template ? (row[column.field] ? row[column.field] : column.default) : ''}
                        {column.template ? <span dangerouslySetInnerHTML={{__html: (column.template(row))}}></span> : ''}
                    </td>
                )}
            </tr>
        )}
        </tbody>
    )
}
function Footer(props) {
    const pages = Math.floor(props.count / props.onPage);

    const leftRightCount = 2;

    const from = Math.max(1, Math.min(pages - leftRightCount * 2, Math.max(1, props.currentPage - leftRightCount)));
    var arr = (function (a, b) {
        while (a--)b[a] = a + from;
        return b
    })(Math.min(leftRightCount * 2 + 1, pages > 0 ? pages : 1), []);

    return (
        <tr>
            <td colSpan={props.columns.length}>
                <div className="w-table-footer-all">
                    Wszystkich <span>{props.count}</span>
                </div>
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
                            <option value={x}>{x}</option>
                        )}
                    </select>
                </div>

            </td>
        </tr>
    )
}

export {Table}