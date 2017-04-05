import React, {Component} from 'react';
import {Calendar} from 'react-date-range';

class DateFilter extends Component {

    constructor(props) {
        super(props)

        this.state = {
            show: false
        }

    }

    render() {
        return (
            <div>
                <div className="w-filter-trigger" onClick={(e) => this.setState({show: !this.state.show})}><i className="fa fa-filter"></i></div>

                {this.state.show ?
                    <div className="w-filter-body">
                        <Calendar
                            rangedCalendars={true} calendars={2} twoStepChange={true}
                        />
                        {JSON.stringify(this.props, null, 2)}xx
                    </div>
                    : ''}


            </div>
        )
    }

}

export {DateFilter}