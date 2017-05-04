import React, {Component} from 'react';


export class Button extends Component {

    constructor(props) {
        super(props)
        this.state = {
            ajaxState: 'INIT',
            disabled: false,
            response: {},
        }

        this.changeAjaxStateTimeout = null;
    }

    componentDidMount() {

    }

    componentWillUpdate(nextProps, nextState) {
        if (
            ( nextState.ajaxState == "FETCHED" ||
            nextState.ajaxState == "ERROR" )
            && this.state.disabled == false
        ) {
            if (this.props.ajaxBehavior == 'BACK_TO_INIT') {
                this.changeAjaxStateTimeout = setTimeout(() => {
                    this.setState({ajaxState: 'INIT'});
                }, 1000)
            } else if (this.props.ajaxBehavior == 'DISABLE_AFTER') {
                this.setState({disabled: true});
            }
        }
    }

    handleClick(e) {
        if (this.props.events.click) {
            this.props.events.click.map((callback) => {
                callback.bind(this)(e, this.props.context)
            });
        }
        if (this.props.ajaxAction) {
            this.xhr(this.props.ajaxAction, {})
        }
    }

    xhr(url, data, method = "POST", datatype = "json") {

        this.setState({ajaxState: 'FETCHING'});

        return $.ajax({
            dataType: datatype,
            url: url,
            data: data,
            method: method
        }).done((data) => {
            clearTimeout(this.changeAjaxStateTimeout);


            if (this.props.ajaxSuccessDefinition(data)) {
                //
                if (this.props.events.ajaxSuccess) {
                    this.props.events.ajaxSuccess.map((callback) => {
                        callback.bind(this)(data, this.props.context)
                    });
                }
                this.setState({ajaxState: 'FETCHED', response: data});
            } else {

                if (this.props.events.ajaxError) {
                    this.props.events.ajaxError.map((callback) => {
                        callback.bind(this)(data, this.props.context)
                    });
                }
                this.setState({ajaxState: 'ERROR', response: data});
            }

        }).fail(() => {
            this.setState({ajaxState: 'ERROR'});
        });

    }

    render() {

        //console.log(this.props)
        let label = this.props.label
        let icon = '';
        let className = this.props.class.slice(0);
        if (this.props.icon) {
            icon = <i className={'fa ' + this.props.icon}></i>
        }

        if (this.props.ajaxStates[this.state.ajaxState]) {
            label = this.props.ajaxStates[this.state.ajaxState].label;
            className.push(this.props.ajaxStates[this.state.ajaxState].class)
            if (this.props.ajaxStates[this.state.ajaxState].icon) {
                icon = <i className={'fa ' + this.props.ajaxStates[this.state.ajaxState].icon}></i>
            }
        }

        return (
            <div className={'w-button'}>
                <button disabled={this.state.disabled} className={className.join(' ')} onClick={this.handleClick.bind(this)}>{icon} {label} </button>
            </div>


        )
    }

}

