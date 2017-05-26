import React, {Children} from "react"
import {Text, Select, Switch, CheckboxGroup, Textarea} from "ctrl/Fields"
import PropTypes from 'prop-types';

const withBootstrapFormField = (Field) => {


    return class BootstrapFieldContainer extends React.Component {

        static propsTypes = {
            label: PropTypes.string,
            help: PropTypes.string,
            form: PropTypes.obj
        }

        render() {
            let props = this.props;

            let classes = ['form-group'];

            if (this.props.errors) {
                classes.push('has-error')
            }

            if (props.layoutType == 'horizontal') {

                return (
                    <div className={classes.join(' ')}>
                        <label className="col-sm-2 control-label">{props.label}</label>
                        <div className="col-sm-10"><Field {...props} className="form-control"/></div>
                    </div>
                )
            }
            if (props.layoutType == 'default' || props.layoutType == 'inline' || !props.layoutType) {
                return (
                    <div className={classes.join(' ')}>
                        <label >{this.props.label}</label>
                        <Field {...this.props} className="form-control"/>

                        {props.help ?
                            <span class="help-block">{props.help} </span>
                            : ''}
                        {props.errors ?
                            <span className="help-block">{props.errors.join(', ')} </span>

                            : ''}
                        {/*.join(", ")*/}
                    </div>
                )
            }
        }
    }
}

class BForm extends React.Component {

    static defaultProps = {
        layoutType: "default",

        data: {},

    }

    static propsTypes = {
        layoutType: PropTypes.oneOf(['default', 'inline', 'horizontal']),
        onChange: PropTypes.fun,
        onSubmit: PropTypes.fun,
        onBeforeSend: PropTypes.fun,
        onValidatorError: PropTypes.fun,
        onError: PropTypes.fun,
        onSuccess: PropTypes.fun,
        data: PropTypes.obj,
        action: PropTypes.string,

    }

    constructor(props) {
        super(props);
        this.state = {
            data: props.data,
            fieldErrors: {},
            formErrors: {},
            isDirty: false,
            loading: false,
        };
    }

    getData() {
        return this.state.data;
    }

    handleValidatorError(response) {
        if (this.props.onValidatorError) {
            this.props.onValidatorError(e);
        }
        console
        this.setState({
            fieldErrors: response.fieldErrors,
            formErrors: response.errors,
        })

        this.forceUpdate();
    }

    handleSubmit(e) {
        e.preventDefault();

        if (this.props.onSubmit) {
            this.props.onSubmit(e);
        } else {
            if (this.props.action) {

                if (this.props.onBeforeSend) {
                    this.props.onBeforeSend(e);
                }
                $.post(this.props.action, {data: this.getData()}, (response) => {
                    try {
                        let data = JSON.parse(response)
                        if (data.errors === undefined) {

                            if (this.props.onSuccess) {
                                this.props.onSuccess(e);

                                this.setState({
                                    fieldErrors: {},
                                    formErrors: [],
                                })
                            }
                        } else {
                            alertify.error('Popraw formularz');
                            this.handleValidatorError(data)
                        }
                    } catch (e) {
                        this.debugError(e.message + "<hr />" + response);
                        if (this.props.error) {
                            this.props.onError(e);
                        }
                    }
                });
            }
        }
        return false;
    }

    debugError(error) {
        let errorWindow = window.open("", "", "width=800,height=600");
        errorWindow.document.write(error);
        errorWindow.focus();
    }

    handleInputChange(e) {
        let name = e.target.getAttribute('name');
        let type = e.target.getAttribute('type')

        if (type == "checkbox") {
            let checked = e.target.checked;

            if (!Array.isArray(this.state.data[name])) {
                if (!checked) {
                    this.state.data[name] = [];
                } else {
                    this.state.data[name] = [e.target.value];
                }
            } else {
                if (checked) {
                    this.state.data[name].push(e.target.value);
                } else {
                    let index = this.state.data[name].indexOf(e.target.value);
                    this.state.data[name].splice(index, 1);
                }

            }

        } else {
            this.state.data[name] = e.target.value;
        }
        this.setState({data: this.state.data, isDirty: true});

        if (this.props.onChange) {
            this.props.onChange(e);
        }

    }

    renderChildren(children) {
        return React.Children.map(children, child => {

            if (child.type.name == "BootstrapFieldContainer") {

                if (this.state.data[child.props.name] === undefined) {
                    this.state.data[child.props.name] = null;
                }
                if (this.state.fieldErrors[child.props.name] === undefined) {
                    this.state.fieldErrors[child.props.name] = null;
                }
                return React.cloneElement(child, {
                    value: this.props.data[child.props.name],
                    layoutType: this.props.layoutType,
                    errors: this.state.fieldErrors[child.props.name],
                    onChange: (e) => {

                        this.handleInputChange(e);
                    }
                })
            } else {
                return child
            }
        })
    }

    applyToField(name) {

        if (this.state.data[name] === undefined) {
            this.state.data[name] = null;
        }
        if (this.state.fieldErrors[name] === undefined) {
            this.state.fieldErrors[name] = null;
        }
        return {
            value: this.props.data[name],
            layoutType: this.props.layoutType,
            errors: this.state.fieldErrors[name],
            onChange: (e) => {
                this.handleInputChange(e);
            }
        }
    }

    render() {
        const layoutType = this.props.layoutType;

        let classes = [];
        if (layoutType == "horizontal") {
            classes.push('form-horizontal');
        } else if (layoutType == "inline") {
            classes.push('form-inline');
        }

        return (
            <form className={classes.join(' ')} onSubmit={this.handleSubmit.bind(this)}>
                {this.state.formErrors.length > 0 ?
                    <ul className="bg-danger ">
                        {this.state.formErrors.map(el => <li>{el}</li>)}
                    </ul>
                    :
                    ''
                }

                {typeof this.props.children == "function" ?
                    this.props.children(this.applyToField.bind(this))
                    :
                    this.renderChildren(this.props.children)
                }

                {layoutType == 'horizontal' ?
                    <div className="form-group">
                        <div className="col-sm-10 col-sm-offset-2">
                            <button className="btn btn-white" type="submit">Anuluj</button>
                            <button className="btn btn-primary" type="submit">Zapisz</button>
                        </div>
                    </div> : ''}
            </form>
        )
    }
}

const BText = withBootstrapFormField(Text);
const BTextarea = withBootstrapFormField(Textarea);
const BSelect = withBootstrapFormField(Select);
const BSwitch = withBootstrapFormField(Switch);
const BCheckboxGroup = withBootstrapFormField(CheckboxGroup);
export{BForm, BText, BSwitch, BSelect, BCheckboxGroup, BTextarea};