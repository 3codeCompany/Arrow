import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import {DateRangePicker} from 'react-dates';
import 'react-dates/lib/css/_datepicker.css';
import 'rc-time-picker/assets/index.css';
import TimePicker from 'rc-time-picker';
import moment from 'moment'

moment.locale('pl');


class Filter extends Component {
    constructor(props) {
        super(props)

        this.state = {
            show: false,
        }

    }

    componentDidUpdate(nextProps, nextState) {

        if (this.state.show == true) {
            let data = this.refs.body.getBoundingClientRect();
            if (data.right > window.innerWidth) {
                this.refs.body.style.right = 0;

            } else {

            }
        }

        return true
    }

    onBlur(e) {
        var currentTarget = e.currentTarget;

        setTimeout(() => {
            if (!currentTarget.contains(document.activeElement)) {
                this.setState({show: false})
            }
        }, 100);
    }
}

class DateFilter extends Filter {

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

        this.setState({show: false});

        let dateStart = this.state.startDate.format('YYYY-MM-DD');
        let timeStart = this.state.startTime.format('HH:mm:ss');
        let dateStop = this.state.endDate.format('YYYY-MM-DD');
        let timeStop = this.state.endTime.format('HH:mm:ss');

        let separatorI = '<i class="fa fa-arrow-right"></i>';
        let calendarI = ''; //'<i class="fa fa-calendar-o"></i>';
        let clockI = '<i class="fa fa-clock-o"></i>';

        let val = `${dateStart} ${timeStart} : ${dateStop} ${timeStop}`;
        let label = `${calendarI} ${dateStart} ${clockI} ${timeStart} ${separatorI} ${calendarI} ${dateStop} ${clockI} ${timeStop}`;
        if (timeStart == "00:00:00" && timeStop == "23:59:59")
            label = `${dateStart} ${separatorI} ${dateStop}`;

        let table = window.Serenity.get($(ReactDOM.findDOMNode(this)).parents('.serenity-widget:eq(0)')[0]);
        table.data.addFilter(this.props.field, val, '<x<in', this.props.caption, ':', label);
        table.refresh();

    }

    render() {
        return (
            <div className={'w-filter w-filter-date ' + (this.state.show ? 'w-filter-opened' : '')}
                 onBlur={this.onBlur.bind(this)}
                 ref="container"
                 tabIndex="0"
            >
                <div className="w-filter-trigger" onClick={(e) => this.setState({show: !this.state.show})}><i className="fa fa-filter"></i></div>

                {this.state.show ?
                    <div className="w-filter-body" ref="body">
                        <i className="fa fa-calendar-o"></i>
                        <DateRangePicker
                            startDate={this.state.startDate} // momentPropTypes.momentObj or null,
                            endDate={this.state.endDate} // momentPropTypes.momentObj or null,
                            onDatesChange={({startDate, endDate}) => this.setState({startDate, endDate})} // PropTypes.func.isRequired,
                            focusedInput={this.state.focusedInput} // PropTypes.oneOf([START_DATE, END_DATE]) or null,
                            onFocusChange={focusedInput => {
                                if (focusedInput == null) {
                                    this.refs.container.focus()
                                }
                                this.setState({focusedInput});
                            }} // PropTypes.func.isRequired,
                            startDatePlaceholderText="Data od"
                            endDatePlaceholderText="Data do"
                            minimumNights={0}
                            isOutsideRange={() => {
                                return false
                            }}
                            onPrevMonthClick={() => this.refs.container.focus()}
                            onNextMonthClick={() => this.refs.container.focus()}
                        />
                        <div className="w-filter-date-time">
                            <i className="fa fa-clock-o"></i>
                            <div>
                                <TimePicker defaultValue={this.state.startTime} showSecond={false}
                                            onChange={(value) => {
                                                if (value) {
                                                    this.setState({startTime: value})

                                                }
                                                this.refs.container.focus()
                                            }}
                                            onClose={() => {
                                                setTimeout(() => this.refs.container.focus(), 20);
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
                                            onChange={(value) => {
                                                if (value) {
                                                    this.setState({endTime: value})
                                                }
                                                this.refs.container.focus()
                                            }}
                                            onClose={() => {
                                                setTimeout(() => this.refs.container.focus(), 20);
                                            }}
                                            value={this.state.endTime}
                                />
                            </div>
                        </div>
                        <div>
                            <button className="w-filter-apply" onClick={this.handleApply.bind(this)}>Zastosuj</button>
                        </div>
                    </div>
                    : ''}

            </div>
        )
    }

}
class SelectFilter extends Filter {

    handleApply() {
        this.setState({show: false});

        let select = ReactDOM.findDOMNode(this.refs.value);
        let values = [].filter.call(select.options, function (o) {
            return o.selected;
        }).map(function (o) {
            return o.value;
        });
        let labels = [].filter.call(select.options, function (o) {
            return o.selected;
        }).map(function (o) {
            return o.innerHTML;
        });


        let table = window.Serenity.get($(ReactDOM.findDOMNode(this)).parents('.serenity-widget:eq(0)')[0]);
        table.data.addFilter(this.props.field, values, 'IN', this.props.caption, ':', labels.join(", "));
        table.refresh();

    }

    _handleKeyPress(e) {
        if (e.key === 'Enter') {
            this.handleApply();
        }
    }

    render() {
        return (
            <div className={'w-filter w-filter-select ' + (this.state.show ? 'w-filter-opened' : '')}
                 onBlur={this.onBlur.bind(this)}
                 ref="container"
                 tabIndex="0"
            >
                <div className="w-filter-trigger" onClick={(e) => this.setState({show: !this.state.show})}><i className="fa fa-filter"></i></div>

                {this.state.show ?
                    <div className="w-filter-body" ref="body">
                        <select autoFocus ref="value" name="" id="" multiple={this.props.multiselect}
                                size={this.props.multiselect ? this.props.content.length : 1}
                                onKeyPress={this._handleKeyPress.bind(this)}
                        >
                            {this.props.multiselect ? '' :
                                <option value="0">Wybierz opcję</option>
                            }
                            {Object.entries(this.props.content).map((el) =>
                                <option
                                    key={el[0]}
                                    value={el[0]}
                                    onMouseDown={(e) => {
                                        e.preventDefault();
                                        $(e.currentTarget).prop('selected', $(e.currentTarget).prop('selected') ? false : true);
                                        return false;
                                    }}
                                >{el[1]}</option>
                            )}
                        </select>
                        <div>
                            <button className="w-filter-apply" onClick={this.handleApply.bind(this)}>Zastosuj</button>
                        </div>
                        {/*<pre>{JSON.stringify(this.props, null, 2)}</pre>*/}
                    </div>
                    : ''}

            </div>
        )
    }

}

SelectFilter.defaultProps = {
    multiselect: false,

};

SelectFilter.propTypes = {
    multiselect: React.PropTypes.bool
}

class SwitchFilter extends Filter {

    constructor(props) {
        super(props)

        this.state = {
            show: false,

        }

    }

    handleApply() {
        this.setState({show: false});
        let table = window.Serenity.get($(ReactDOM.findDOMNode(this)).parents('.serenity-widget:eq(0)')[0]);
        table.data.addFilter(this.props.field, val, '<x<in', this.props.caption, ':', label);
        table.refresh();

    }

    render() {
        return (
            <div className={'w-filter w-filter-switch ' + (this.state.show ? 'w-filter-opened' : '')}
                 onBlur={this.onBlur.bind(this)}
                 ref="container"
                 tabIndex="0"
            >
                <div className="w-filter-trigger" onClick={(e) => this.setState({show: !this.state.show})}><i className="fa fa-filter"></i></div>

                {this.state.show ?
                    <div className="w-filter-body" ref="body">

                        {Object.entries(this.props.content).map((el) =>
                            <div>
                                <label htmlFor={el[0]}>
                                    <input
                                        name="switch"
                                        type="radio"
                                        key={el[0]}
                                        id={el[0]}
                                        value={el[0]}
                                        onMouseDown={(e) => {
                                            e.preventDefault();
                                            $(e.currentTarget).prop('selected', $(e.currentTarget).prop('selected') ? false : true);
                                            return false;
                                        }}
                                    /> {el[1]}</label>
                            </div>
                        )}

                        <div>
                            <button className="w-filter-apply" onClick={this.handleApply.bind(this)}>Zastosuj</button>
                        </div>
                        {/*<pre>{JSON.stringify(this.props, null, 2)}</pre>*/}
                    </div>
                    : ''}

            </div>
        )
    }

}
class NumericFilter extends Filter {

    constructor(props) {
        super(props)
        this.state.option = "=";
    }

    handleApply() {
        this.setState({show: false});
        let table = window.Serenity.get($(ReactDOM.findDOMNode(this)).parents('.serenity-widget:eq(0)')[0]);
        let val, label;
        if (this.state.option != "IN") {
            val = this.refs.input1.value;
            if (this.state.option == "<x<") {
                val += "-" + this.refs.input2.value
            }
            label = val;
        } else {
            val = this.refs.input3.value.split('\n');
            label = val.join(", ")
            if (label.length > 50) {
                label = label.substring(0, 50) + '....';
            }

        }

        table.data.addFilter(this.props.field, val, this.state.option, this.props.caption, ':', label);
        table.refresh();

    }

    _handleKeyPress(e) {
        if (e.key === 'Enter') {
            this.handleApply();
        }
    }

    render() {
        const options = {
            '=': 'równa',
            '<': 'mniejsza',
            '<=': 'mniejsza równa',
            '>': 'większa',
            '>=': 'większa równia',
            '<x<': 'pomiędzy',
            'IN': 'wiele wartości ( rozdziel enterem )',
        };
        return (
            <div className={'w-filter w-filter-numeric ' + (this.state.show ? 'w-filter-opened' : '')}
                 onBlur={this.onBlur.bind(this)}
                 ref="container"
                 tabIndex="0"
            >
                <div className="w-filter-trigger" onClick={(e) => this.setState({show: !this.state.show})}><i className="fa fa-filter"></i></div>


                {this.state.show ?
                    <div className="w-filter-body" ref="body">
                        {this.state.option == "<x<" ?
                            <div className="w-filter-label">Od</div>
                            : ''}

                        {this.state.option != "IN" ?
                            <input type="" autoFocus ref="input1" onKeyPress={this._handleKeyPress.bind(this)}/>
                            :
                            <textarea type="" autoFocus ref="input3"/>
                        }

                        {this.state.option == "<x<" ?
                            <div className="w-filter-label">Do
                                <input type="" ref="input2" onKeyPress={this._handleKeyPress.bind(this)}/></div>
                            : ''}
                        <select name="" id=""
                                onChange={(e) => this.setState({option: e.currentTarget.value})}
                                value={this.state.option}
                        >
                            {Object.entries(options).map(([key, val]) =>
                                <option value={key} key={key}> {val}</option>
                            )}
                        </select>
                        <div>
                            <button className="w-filter-apply" onClick={this.handleApply.bind(this)}>Zastosuj</button>
                        </div>
                        {/*<pre>{JSON.stringify(this.props, null, 2)}</pre>*/}
                    </div>
                    : ''}

            </div>
        )
    }

}


class TextFilter extends Filter {

    constructor(props) {
        super(props)

        this.state = {
            show: false,
            option: "LIKE"

        }

        this.options = {"LIKE": "zawiera", "==": "r\u00f3wny", "!=": "r\u00f3\u017cne", "NOT LIKE": "nie zawiera", "^%": "zaczyna si\u0119 od", "%$": "ko\u0144czy si\u0119 na"};

    }

    handleApply() {
        this.setState({show: false});
        const value = this.refs.input.value;
        if (value) {
            let table = window.Serenity.get($(ReactDOM.findDOMNode(this)).parents('.serenity-widget:eq(0)')[0]);
            table.data.addFilter(this.props.field, value, this.state.option, this.props.caption, this.options[this.state.option] + " :", this.refs.input.value);
            table.refresh();
        }

    }

    _handleKeyPress(e) {
        if (e.key === 'Enter') {
            this.handleApply();
        }
    }

    render() {

        return (
            <div className={'w-filter w-filter-text ' + (this.state.show ? 'w-filter-opened' : '')}
                 onBlur={this.onBlur.bind(this)}
                 ref="container"
                 tabIndex="0"
            >
                <div className="w-filter-trigger" onClick={(e) => this.setState({show: !this.state.show})}><i className="fa fa-filter"></i></div>

                {this.state.show ?
                    <div className="w-filter-body" ref="body">
                        <input type="" autoFocus onKeyPress={this._handleKeyPress.bind(this)} ref="input"/>

                        <select name="" id=""
                                onChange={(e) => this.setState({option: e.currentTarget.value})}
                                value={this.state.option}

                        >
                            {Object.entries(this.options).map(([key, val]) =>
                                <option value={key} key={key}> {val}</option>
                            )}
                        </select>

                        <div>
                            <button className="w-filter-apply" onClick={this.handleApply.bind(this)}>Zastosuj</button>
                        </div>
                    </div>
                    : ''}

            </div>
        )
    }

}

export {DateFilter, SelectFilter, SwitchFilter, NumericFilter, TextFilter}