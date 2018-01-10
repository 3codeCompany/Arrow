import React, {Component} from "react";

import Navbar from "frontend/src/ctrl/Navbar"
import {confirm} from "frontend/src/ctrl/Overlays"
import {Table, Column} from "frontend/src/ctrl/Table"
import Panel from "frontend/src/ctrl/Panel"
import {Row} from "frontend/src/layout/BootstrapLayout"
import Comm from "frontend/src/lib/Comm";
import {CheckboxGroup} from "frontend/src/ctrl/Fields";


export default class access_access_users_list extends Component {
    constructor(props) {
        super(props);
        this.state = {};
    }

    handleDelete(row, event) {
        confirm(`Czy na pewno usunąć "${row.point_object_friendly_id}" ?`).then(() => {

            Comm._post(this.props.baseURL + '/delete', {key: row.id}).then(() => {
                this.props._notification(`Punkt  "${row.point_object_friendly_id}" został usunięta.`);
                this.table.load();
            });

        });
    }


    saveAccessPoint(row) {

        Comm._post(this.props.baseURL + '/save', {key: row.id, data: {groups: row.groups, control_enabled: row.control_enabled}}).then(() => {
            this.props._notification(`Punkt  "${row.point_object_friendly_id}" został zaktualizowany.`);

            //this.table.load();
        });
    }

    render() {
        return (

            <div>
                <Navbar>
                    <span>System</span>
                    <span>Usawienia dostępu</span>
                </Navbar>

                <div className="panel-body-margins">

                    <Table
                        remoteURL={this.props.baseURL + '/getData'}
                        ref={(table) => this.table = table}
                        columns={[
                            Column.id('id', 'Id').headerTooltip('To jest bardzo bardzo ciekawe'),

                            //Column.text("point_action", "Nazwa").headerTooltip("Inna sprawa"),
                            Column.text('point_object_friendly_id', 'Nazwa'),

                            Column.bool('control_enabled', 'Kontrola')
                                .onClick((row, val, event, rowComponent) => {
                                    row.control_enabled = row.control_enabled == '1' ? '0' : '1';
                                    this.saveAccessPoint(row);
                                    -rowComponent.forceUpdate();
                                })
                            ,
                            Column.text('groups', 'Grupy dostępu')
                                .onClick((row, column, event, rowComponent) => {
                                    if (event.target.tagName == 'DIV' || event.target.tagName == 'TD' || event.target.tagName == 'I') {
                                        row.edited = !row.edited;
                                    }
                                    rowComponent.forceUpdate();
                                })
                                .template((val, row) => {
                                    let v = parseInt(val || 0);
                                    let selected = [];
                                    let selectedNames = [];
                                    Object.entries(this.props.agroups).map(([id, name]) => {
                                        if (v & id) {
                                            selected.push(id);
                                            selectedNames.push(name);
                                        }
                                    });
                                    if (row.edited) {
                                        return <div>
                                            <CheckboxGroup
                                                value={selected}
                                                options={this.props.agroups}
                                                onChange={(x) => {
                                                    if (x.event.target.checked) {
                                                        row.groups = v + parseInt(x.event.target.value);
                                                    } else {
                                                        row.groups = v - parseInt(x.event.target.value);
                                                    }
                                                    this.saveAccessPoint(row);
                                                }}
                                            />
                                        </div>;
                                    } else {


                                        return <div>{selectedNames.length > 0 ? selectedNames.join(', ') : <div className={'center'}><i className="fa fa-times  lightgrey"></i></div>}</div>;
                                    }
                                })
                            ,
                            Column.template('Zobacz', () => <i className="fa fa-times"></i>)
                                .onClick(this.handleDelete.bind(this))
                                .className('center darkred')
                        ]}
                    />

                </div>
            </div>
        );
    }
}
