import React, {Component} from 'react';

import Navbar from 'frontend/src/ctrl/Navbar'
import {confirm} from 'frontend/src/ctrl/Overlays'
import {Table, Column} from 'frontend/src/ctrl/Table'
import Panel from 'frontend/src/ctrl/Panel'
import {Row} from 'frontend/src/layout/BootstrapLayout'
import Comm from 'frontend/src/lib/Comm';

export default class access_access_users_list extends Component {
    constructor(props) {
        super(props);
        this.state = {};
    }

    handleDelete(row, event) {
        confirm(`Czy na pewno usunąć "${row.name}" ?`).then(() => {
            Comm._post(this.props.baseURL + '/delete', {key: row.id}).then(() => {
                this.props._notification(`Grupa  "${row.name}" została usunięta.`)
                this.table.load();
            })
        });
    }

    render() {
        return (
            <div>
                <Navbar>
                    <span>System</span>
                    <span>Grupy dostępu</span>
                </Navbar>
                <Row>
                    <Panel title="Lista grup dostępu systemu"
                           toolbar={[
                               <a href={'#' + this.props.baseURL + '/edit'} className="btn btn-sm btn-primary"><i className="fa fa-plus"></i>Dodaj</a>
                           ]}
                    >
                        <Table
                            remoteURL={this.props.baseURL + '/getData'}
                            ref={(table) => this.table = table}
                            columns={[
                                Column.id('id', 'Id'),
                                Column.text('name', 'Nazwa'),
                                Column.template('Zobacz', () => <i className="fa fa-search"></i>)
                                    .onClick((row) => window.location.hash = this.props.baseURL + `/edit?key=${row.id}`)
                                    .className('center darkgreen'),
                                Column.template('Zobacz', () => <i className="fa fa-times"></i>)
                                    .onClick(this.handleDelete.bind(this))
                                    .className('center darkred')
                            ]}
                        />
                    </Panel>
                </Row>
            </div>
        )
    }
}