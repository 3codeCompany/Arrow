import React, {Component} from 'react';

import {DateFilter, SelectFilter, NumericFilter, SwitchFilter, TextFilter} from './Filters'

class Table extends Component {

    constructor(props) {
        super(props);
        var x;
        this.props.columns.map(el => {
            if (el.template) {
                el.template = eval('x = function(row){ return `' + el.template + '`; };')
            }

            if (typeof(el.events) == 'object') {
                Object.entries(el.events).map(([_key, val]) => {
                    el.events[_key] = eval('x = function(row){ ' + val + '; return false; };');
                });
            }

        });

        this.state = {
            loaded: false,
            data: [],
            dataSourceError: false,
            filters: {},
            onPage: this.props.onPage,
            currentPage: 1
        };

        this.load();


    }


    load() {
        this.state.dataSourceError = false;
        let xhr = new XMLHttpRequest();
        xhr.onload = (e) => {
            if (xhr.status === 200) {
                let parsed = {data: [], countAll: 0};
                try {
                    parsed = JSON.parse(xhr.responseText)
                } catch (e) {
                    this.setState({dataSourceError: xhr.responseText})
                }

                this.setState({
                    data: parsed.data,
                    countAll: parsed.countAll,
                    loaded: true
                });
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

    headClicked(index, e) {
        if (e.target.tagName != "TH") {
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

    handleCurrentPageChange(page) {
        this.setState({currentPage: page}, this.load);
    }

    render() {
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
                <h4>Super react table</h4>


                <FiltersPresenter columns={columns} filters={this.state.filters}/>
                <table>
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
                        onPageChanged={(onPage) => alert(onPage)}
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
                        <div className="remove"><i className="fa fa-times"></i></div>
                    </div>
                )}

            {Object.entries(props.filters).map(([key, el]) =>
                <div>
                    <div><i className="fa fa-filter"></i></div>
                    <div className="caption">{el.caption}</div>
                    <div className="value" dangerouslySetInnerHTML={{__html: el.label}}/>
                    <div className="remove"><i className="fa fa-times"></i></div>
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
            <td className="w-table-center" colSpan={props.colspan}><h4>Brak danych</h4></td>
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
                        onClick={column.events.click ? function () {
                            column.events.click(row);
                        } : function () {
                        }}
                        className={'' + (column.events.click ? 'w-table-cell-clickable' : '') + (column.class ? ' ' + column.class.join(' ') : '')}
                    >
                        {column.field && !column.template ? (row[column.field] ? row[column.field] : column.default) : ''}
                        {column.template ? <span dangerouslySetInnerHTML={{__html: (eval(column.template)(row))}}></span> : ''}
                    </td>
                )}
            </tr>
        )}
        </tbody>
    )
}
function Footer(props) {
    const pages = Math.ceil(props.count / props.onPage);

    return (
        <tr>
            <td colSpan={props.columns.length}>
                Wszystkich {props.count}
                <br/>
                na stronie : {props.onPage} , strona: {props.currentPage} , stron: {pages}
                <div className="w-table-pager">
                    {Array(Math.min(props.currentPage - 1, 5)).fill(1).map((el, i) =>
                        <div key={i} onClick={(e) => {
                            props.currentPageChanged(props.currentPage - Math.min(props.currentPage - 1, 5) + i)
                        }}>{props.currentPage - Math.min(props.currentPage - 1, 5) + i}</div>
                    )}
                    <div className="w-table-pager-active">{props.currentPage}</div>
                    {Array(Math.min(pages - props.currentPage, 5)).fill(1).map((el, i) =>
                        <div key={i} onClick={(e) => {
                            props.currentPageChanged(props.currentPage + 1 + i)
                        }}>{props.currentPage + 1 + i}</div>
                    )}
                </div>

            </td>
        </tr>
    )
}

export {Table}