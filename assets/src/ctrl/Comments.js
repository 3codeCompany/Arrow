import React, {Component} from 'react';
import PropTypes from 'prop-types';

class Comments extends Component {


    propTypes = {
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
            <div className="w-comments">
                {p.children}
            </div>
        )
    }
}

const CommentItem = (props) => {
    return (
        <div className="comment">
            <a className="avatar">
                <img src="https://semantic-ui.com/images/avatar/small/matt.jpg" alt=""/>
            </a>
            <div className="content">
                <a className="author">{props.author}</a>
                <div className="metadata">
                    <span className="date">{props.time}</span>
                </div>
                <div className="text">
                    {props.children}
                </div>
                {/*<div className="actions">
                    <a className="reply">Reply</a>
                </div>*/}
            </div>
        </div>

    )
}

export {Comments, CommentItem}