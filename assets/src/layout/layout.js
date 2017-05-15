import React, {Component} from 'react';
import PropTypes from 'prop-types';

class Panel extends Component {

    static propTypes = {
        title: PropTypes.string,
        noPadding: PropTypes.bool,
        noBottomMargin: PropTypes.bool,
    };
    static defaultProps = {
        noPadding: false,
        noBottomMargin: true
    }

    render() {
        const props = this.props
        let classes = ['panel']
        if (this.props.noPadding) {
            classes.push('panel-no-padding')
        }
        if (this.props.noBottomMargin) {
            classes.push('panel-no-bottom-margin')
        }
        return (
            <div className={classes.join(" ")}>
                <div className="panel-body">
                    {props.title ? <div className="title">{props.title}
                        <div className="panel-toolbar">{props.toolbar}</div>
                    </div> : '' }
                    {props.children}
                </div>
            </div>
        )
    }
}


const Navbar = (props) => {

    let children = Array.isArray(props.children) ? props.children : [props.children];

    return (
        <div className="row">
            <div className="col-md-12">
                <nav className="navbar " role="navigation">
                    <div className="collapse navbar-collapse">
                        <ul className="nav navbar-nav">

                            <li>
                                <ol className="breadcrumb">
                                    {children.map((child, key) =>
                                        <li key={key} className={key + 1 == props.children.length ? 'active' : ''}>
                                            {child}
                                        </li>
                                    )}

                                </ol>
                            </li>
                            {/*<li>Tutaj coś  jeszcze może być</li>*/}
                        </ul>
                        <div style={{float: 'right'}}>
                            <div style={{display: 'table-cell', height: '50px', verticalAlign: 'middle'}}>
                                {props.toolbar}
                            </div>
                        </div>
                    </div>

                </nav>
            </div>
        </div>
    )
}

Navbar.propTypes = {
    children: PropTypes.node.isRequired,
}

const Container = (props) => {
    return (
        <div className="container">{props.children}</div>
    )
}
Container.propTypes = {
    children: PropTypes.node.isRequired,
}


const Row = (props) => {

    const children = Array.isArray(props.children) ? props.children : [props.children];

    let colMd = 0
    let colsMd = [];
    //if detailed width delivered
    if (props.md) {
        let sum = props.md.reduce((a, b) => a + b, 0);
        if (sum > 12) {
            throw new Error(`To many columns ${sum}`);
        }
        //calculating width for rest of columns
        colMd = (12 - sum) / (children.length - props.md.length );
        colsMd = props.md;
    } else {
        //equal width for each element
        colMd = 12 / children.length;
    }

    //adding calculated default row width
    for (let i = colsMd.length; i < 12; i++) {
        colsMd[i] = Math.floor(colMd);
    }

    return (
        <div className="row">
            {children.map((child, key) =>
                <div key={key} style={{padding: 0}} className={"col-md-" + colsMd[key]}>{child}</div>
            )}
        </div>
    )
}

Row.propTypes = {
    children: PropTypes.node.isRequired,
    md: PropTypes.array
}


const SimpleTable = (props) => {

    let children = props.children;
    if (children == undefined) {
        children = [];
    }

    for (let i in props.fromFlatObject) {
        children.push(<SimpleTableRow key={i} cols={[i, props.fromFlatObject[i]]}/>)
    }

    return (
        <table className="table table-striped">
            <tbody>{children}</tbody>
        </table>
    )
}
SimpleTable.propTypes = {
    fromFlatObject: PropTypes.object
}

SimpleTable.defaultProps = {
    fromFlatObject: {}
}

const SimpleTableRow = (props) => {
    return (
        <tr>
            {props.cols.map((el, index) =>
                <td key={index}>{el}</td>
            )}
        </tr>
    )
}

SimpleTableRow.propTypes = {
    cols: PropTypes.array.isRequired
}


export {Panel, Navbar, Row, Container, SimpleTable, SimpleTableRow}