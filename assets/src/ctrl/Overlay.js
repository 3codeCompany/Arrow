import React, {Component} from 'react';
import PropTypes from 'prop-types';

class Portal extends Component {
    portalElement = null

    static propTypes = {
        children: PropTypes.node.isRequired,

    }

    constructor(props) {
        super(props);
        this.state = {
            currentTab: props.defaultActiveTab || 0
        }
    }

    componentDidMount() {
        var p = this.props.portalId && document.getElementById(this.props.portalId);
        if (!p) {
            var p = document.createElement('div');
            p.id = this.props.portalId;

            document.body.appendChild(p);
        }
        this.portalElement = p;
        this.componentDidUpdate();
    }

    componentWillUnmount() {
        document.body.removeChild(this.portalElement);
    }

    componentDidUpdate() {
        React.render(<div {...this.props}>{this.props.children}</div>, this.portalElement);
    }

    render() {
        return null
    }
}

export {Portal}