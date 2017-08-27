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
        confirm(`Czy na pewno usunąć "${row.login}" ?`).then(() => {
            Comm._post(this.props.baseURL + '/delete', {key: row.id}).then(() => {
                this.props._notification(`Urzytkownik  "${row.login}" został usunięty.`)
                this.table.load();
            })
        });
    }

    render() {
        return (
            <div>
                <Navbar>
                    <span>System</span>
                    <span>Użytkownicy</span>
                </Navbar>
                <Row>
                    <Panel title="Lista użytkowników systemu"
                           toolbar={[
                               <a href={'#' + this.props.baseURL + '/edit'} className="btn btn-sm btn-primary"><i className="fa fa-plus"></i>Dodaj</a>
                           ]}
                    >
                        <Table
                            remoteURL={this.props.baseURL + '/getData'}
                            ref={(table) => this.table = table}
                            columns={[
                                Column.id('id', 'Id'),
                                Column.bool('active', 'Aktywny'),
                                Column.text('login', 'Login'),
                                Column.email('email', 'Email'),
                                Column.template('Grupy dostępu', (val, row) => {
                                    if (row.groups.length > 0) {
                                        return <div><i className="fa fa-lock"></i> {row.groups.join(', ')}</div>
                                    } else {
                                        return <div className="lightgrey center"><i className="fa fa-times"></i></div>
                                    }
                                }),
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