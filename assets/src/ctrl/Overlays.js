import React, {Component} from 'react';
import ReactDOM from 'react-dom'
import PropTypes from 'prop-types';

const withPortal = (ComponentToRender, styles = {}) => {

    return class Portal extends Component {
        portalElement = null

        constructor(props) {
            super(props);
            this.state = {}
        }

        componentDidMount() {
            this.portalElement = document.createElement('div');
            //this.portalElement.classList.add('w-overlay')
            document.body.appendChild(this.portalElement);
            this.componentDidUpdate();
        }

        componentWillUnmount() {
            document.body.removeChild(this.portalElement);
        }


        componentDidUpdate() {

            ReactDOM.render(
                <div className="w-overlay">
                    <ComponentToRender {...this.props} overlayClose={this.handleClose.bind(this)}/>
                </div>

                , this.portalElement);
        }

        handleClose() {
            ReactDOM.unmountComponentAtNode(this.portalElement);
        }

        render() {
            return null
        }
    }

}


class ShadowBody extends Component {
    constructor(props) {
        super(props);
    }

    render() {

        return (<div className="w-shadow">
            <h1>This is shadow</h1>
        </div>)
    }

}

const Shadow = withPortal(ShadowBody,);


class ModalBody extends Component {
    constructor(props) {
        super(props);
        this.state = {
            visible: false
        }
    }

    render() {

        let s = this.state;
        let p = this.props;
        return (<div className="w-modal" ref="body" style={{display: p.visible ? 'block' : 'none'}}>
            {p.visible ? <Shadow /> : null}
            {p.children}
        </div>)
    }

    componentDidUpdate() {
        const container = window.document.getElementsByTagName('body')[0];
        const body = ReactDOM.findDOMNode(this.refs.body);
        const dimentions = body.getBoundingClientRect();
        const containerDimentions = container.getBoundingClientRect();

        const styles = {
            top: ((containerDimentions.height - dimentions.height) / 2 ) + 'px',
            left: ((containerDimentions.width - dimentions.width) / 2) + 'px',

        }
        for (let i in styles) {
            body.style[i] = styles[i];
        }
    }

    componentDidMount() {

    }

}

const Modal = withPortal(ModalBody);
export {Modal, Shadow, withPortal}