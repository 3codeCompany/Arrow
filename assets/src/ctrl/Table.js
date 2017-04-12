import React, {Component} from 'react';

import {DateFilter, SelectFilter, NumericFilter, SwitchFilter, TextFilter} from './Filters'

class Table extends Component {

    constructor(props) {
        super(props);

        this.state = {
            loaded: false,
            data: [],
            dataSourceError: false,
            filters: {}
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
        xhr.send(JSON.stringify({columns: this.props.columns, filters: this.state.filters}));
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
        this.setState({filters: this.state.filters});
        this.load();
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

    render() {
        const columns = this.props.columns;

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
                            const Component = filters[el.filter.type];
                            return (
                                <th key={index} onClick={this.headClicked.bind(this, index)}
                                    style={{width: el.width}}
                                >
                                    {el.order ? <i className={'fa fa-' + (el.order == 'asc' ? 'arrow-down' : 'arrow-up')}></i> : ''}
                                    {el.caption}
                                    {el.filter ? <Component onChange={this.handleFilterChanged.bind(this)} {...el.filter} caption={el.caption} /> : ''}
                                </th>)
                        })}
                    </tr>
                    </thead>

                    {!this.state.loaded && <Loading colspan={columns.length}/>}
                    {this.state.dataSourceError && <Error colspan={columns.length} error={this.state.dataSourceError}/>}
                    {this.state.loaded && this.state.data.length == 0 && <EmptyResult colspan={columns.length}/>}
                    {this.state.loaded && this.state.data.length > 0 && <Rows columns={columns} data={this.state.data}/>}

                    <tfoot>
                    {this.state.loaded && this.state.data.length > 0 && <Footer columns={columns} count={this.state.countAll}/>}
                    </tfoot>
                </table>
                <pre>{JSON.stringify(this.state.filters, null, 2)}</pre>
                <pre>{JSON.stringify(this.props, null, 2)}</pre>
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

            {Object.entries(props.filters).map(([key,el]) =>
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
        <tr>
            <td className="w-table-center" colSpan={props.colspan}>
                <i className="fa fa-spinner fa-spin"></i>
            </td>
        </tr>
    )
}
function EmptyResult(props) {
    return (
        <tr>
            <td className="w-table-center" colSpan={props.colspan}><h4>Brak danych</h4></td>
        </tr>
    )
}


function Error(props) {
    return (
        <tr>
            <td colSpan={props.colspan}>
                <span dangerouslySetInnerHTML={{__html: props.error}}/>
            </td>
        </tr>
    )
}
function Rows(props) {
    return (
        <tbody>
        {props.data.map(row =>
            <tr>
                {props.columns.map(column =>
                    <td>
                        {row[column.field]}
                    </td>
                )}
            </tr>
        )}
        </tbody>
    )
}
function Footer(props) {
    return (
        <tr>
            <td colSpan={5}>
                Wszystkich {props.count}
            </td>
        </tr>
    )
}

export {Table}