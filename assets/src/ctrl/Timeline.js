import React, {Component} from 'react';
import PropTypes from 'prop-types';

class Timeline extends Component {


    static propTypes = {
        children: PropTypes.node.isRequired,
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
            <div className="w-timeline">
                <ul>
                    {p.children.map((child, index) =>
                        <li key={index}>
                            <div className="tail"></div>
                            <div className={'head ' + child.props.color}></div>
                            <div className="content">
                                <p className="content-head">{child.props.head}</p> {child}
                            </div>

                        </li>
                    )}
                </ul>
            </div>
        )
    }
}

const TimelineItem = (props) => {
    return (
        <div className="tab-pane">
            {props.children}
        </div>
    )
}

export {Timeline, TimelineItem}