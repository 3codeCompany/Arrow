import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import {DateRangePicker} from 'react-dates';
import 'react-dates/lib/css/_datepicker.css';
import 'rc-time-picker/assets/index.css';
import TimePicker from 'rc-time-picker';
import moment from 'moment'

moment.locale('pl');

class DateFilter extends Component {

    constructor(props) {
        super(props)

        this.state = {
            show: false,
            startDate: moment(),
            endDate: moment(),
            startTime: moment().startOf('day'),
            endTime: moment().endOf('day')
        }

    }

    handleApply() {
        console.log(this.state.startDate.format('YYYY-MM-DD'));
        console.log(this.state.endDate.format('YYYY-MM-DD'));
        console.log(this.state.startTime.format('HH:mm:ss'));
        console.log(this.state.endTime.format('HH:mm:ss'));
        this.setState({show: false});

        let val = this.state.startDate.format('YYYY-MM-DD') + ' ' + this.state.startTime.format('HH:mm:ss')
        val+= ' : '
        val+= this.state.endDate.format('YYYY-MM-DD') + ' ' + this.state.endTime.format('HH:mm:ss');
        let table = window.Serenity.get( $(ReactDOM.findDOMNode(this)).parents('.serenity-widget:eq(0)')[0] );
        table.data.addFilter(this.props.field, val, '<x<in', this.props.caption, ':', val);
        table.refresh();
        //console.log(parent);


    }

    render() {
        return (
            <div className={'w-filter-date ' + (this.state.show ? 'w-filter-date-opened' : '')}>
                <div className="w-filter-trigger" onClick={(e) => this.setState({show: !this.state.show})}><i className="fa fa-filter"></i></div>

                {this.state.show ?
                    <div className="w-filter-body">
                        <i className="fa fa-calendar-o"></i>
                        <DateRangePicker
                            startDate={this.state.startDate} // momentPropTypes.momentObj or null,
                            endDate={this.state.endDate} // momentPropTypes.momentObj or null,
                            onDatesChange={({startDate, endDate}) => this.setState({startDate, endDate})} // PropTypes.func.isRequired,
                            focusedInput={this.state.focusedInput} // PropTypes.oneOf([START_DATE, END_DATE]) or null,
                            onFocusChange={focusedInput => this.setState({focusedInput})} // PropTypes.func.isRequired,
                            startDatePlaceholderText="Data od"
                            endDatePlaceholderText="Data do"
                            minimumNights={0}
                            isOutsideRange={() => {
                                return false
                            }}
                        />
                        <div className="w-filter-date-time">
                            <i className="fa fa-clock-o"></i>
                            <div>
                                <TimePicker defaultValue={this.state.startTime} showSecond={false}
                                            onChange={(value) => {
                                                if (value) {
                                                    this.setState({startTime: value})
                                                }
                                            }}
                                            value={this.state.startTime}
                                />
                            </div>
                            <div >
                                <svg viewBox="0 0 1000 1000">
                                    <path d="M694.4 242.4l249.1 249.1c11 11 11 21 0 32L694.4 772.7c-5 5-10 7-16 7s-11-2-16-7c-11-11-11-21 0-32l210.1-210.1H67.1c-13 0-23-10-23-23s10-23 23-23h805.4L662.4 274.5c-21-21.1 11-53.1 32-32.1z"></path>
                                </svg>
                            </div>
                            <div>
                                <TimePicker defaultValue={this.state.endTime} showSecond={false}
                                            onChange={({value}) => this.setState({endTime: value})}
                                />
                            </div>
                        </div>
                        <div>
                            <button className="w-filter-apply" onClick={this.handleApply.bind(this)}>Zastosuj</button>
                        </div>
                        {/*<pre>
                        {JSON.stringify(this.props, null, 2)}
                        </pre>*/}
                    </div>
                    : ''}


            </div>
        )
    }

}

export {DateFilter}