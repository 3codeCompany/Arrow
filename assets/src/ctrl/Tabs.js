import React, {Component} from 'react';
import PropTypes from 'prop-types';

class Tabs extends Component {



    static propTypes = {
        children: PropTypes.node.isRequired,
        defaultActiveTab: PropTypes.number
    }

    constructor(props) {
        super(props);
        this.state = {
            currentTab: props.defaultActiveTab || 0
        }
    }


    render() {
        const p = this.props;
        const s = this.state;

        return (
            <div className="w-tabs">
                <div className="tabs-links">
                    {p.children.map((child, index) =>
                        <div key={index} className={(index == s.currentTab ? 'active' : '') + ' ' +(child.props.badge ? 'with-badge' : '')} onClick={e => this.setState({currentTab: index})}>
                            {child.props.icon ?
                                <i className={'fa fa-' + child.props.icon}></i>
                                : null}
                            {child.props.tab}
                            {child.props.badge != undefined ?
                                <div className="w-tabs-badge" >{child.props.badge}</div>
                                : null}
                        </div>
                    )}
                </div>
                <div className="tabs-links-separator"></div>
                <div className="tab-pane-container">
                    {p.children[s.currentTab]}
                </div>
            </div>
        )
    }
}

const TabPane = (props) => {
    return (
        <div className="tab-pane">
            {props.children}
        </div>
    )
}

export {Tabs, TabPane}