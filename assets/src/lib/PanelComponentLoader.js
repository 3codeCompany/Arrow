import React, {Component} from 'react';
import PropTypes from 'prop-types';


export default class PanelComponentLoader extends Component {

    //propTypes = {}

    constructor(props) {
        super(props);
        this.state = {
            propsLoading: false,
            loadedProps: false,
            currComponent: ReactHelper.get(props.component)
        }
    }

    handleReloadProps(input = {}) {
        this.setState({propsLoading: true});
        $.get(this.props.requestURI, {...input, __PROPS_REQUEST__: 1}, (data) => {
            this.setState({propsLoading: false, loadedProps: data});
        });
    }

    handleResolveComponent(path) {
        alert("Resolving component " + path);

    }

    handleGoTo(path, input = {}) {
        this.setState({propsLoading: true});
        $.get(path, {...input, __PROPS_REQUEST__: 1}, (data) => {
            this.setState({propsLoading: false, loadedProps: data, currComponent: ReactHelper.get(data.component)});
        });
    }

    render() {
        const s = this.state;
        const p = s.loadedProps || this.props;
        let Component = s.currComponent;
        return <Component
            {...p}
            reloadProps={this.handleReloadProps.bind(this)}
            goto={this.handleGoTo.bind(this)}
            resolveComponent={this.handleReloadProps.bind(this)}
        />

    }
}