import React, {Component} from 'react';

import {DateFilter, SelectFilter, NumericFilter, SwitchFilter, TextFilter} from './Filters'

class Table extends Component {


    headClidked(index) {

        let currrent = this.props.columns.filter((el) => {
            return el.order
        });

        let prioryty = 1;
        if(currrent.length)
            prioryty = currrent.reduce((pv,cv ) => pv +  cv.orderPrioryty , 0);


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
            this.props.columns[index].orderPrioryty = prioryty ;
        }
        this.setState({});
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

                <div className="w-table-presenter">
                    {columns.filter((el) => {
                        return el.order
                    }).sort((a, b) => a.orderPrioryty > b.orderPrioryty)
                        .map((el, index) =>
                            <span>{el.caption}</span>
                        )}
                </div>
                <table>
                    <thead>
                    <tr>
                        {columns.map((el, index) => {
                            const Component = filters[el.filter.type];
                            return (
                                <th key={index} onClick={this.headClidked.bind(this, index)}>
                                    {el.order ? <i className={'fa fa-' + (el.order == 'asc' ? 'arrow-down' : 'arrow-up')}></i> : ''}
                                    {el.caption}
                                    {el.filter ? <Component {...el.filter} /> : ''}
                                </th>)
                        })}
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td className="w-table-center" colSpan={columns.length}><h4>Brak danych</h4></td>
                    </tr>
                    </tbody>
                </table>
                <pre>{JSON.stringify(this.props, null, 2)}</pre>
            </div>
        )
    }

}

export {Table}