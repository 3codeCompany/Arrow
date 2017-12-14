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
            Comm._post(this.props.baseURL + "/delete", {key: row.id}).then(() => {
                this.props._notification(`Punkt  "${row.point_object_friendly_id}" został usunięta.`)
                this.table.load();
            })
        });
    }


    saveAccessPoint(row) {
        Comm._post(this.props.baseURL + "/save", {key: row.id, data: {groups: row.groups, control_enabled: row.control_enabled}}).then(() => {
            this.props._notification(`Punkt  "${row.point_object_friendly_id}" został zaktualizowany.`)
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
                <Row>
                    <Panel title="Lista grup dostępu systemu" >
                        <Table
                            remoteURL={this.props.baseURL + "/getData"}
                            ref={(table) => this.table = table}
                            rememberState={true}
                            columns={[
                                Column.id("id", "Id"),

                                Column.text("point_action", "Nazwa"),
                                Column.text("point_object_friendly_id", "Nazwa"),

                                Column.bool("control_enabled", "Kontrola")
                                    .onClick((row) =>{
                                        row.control_enabled = row.control_enabled=="1"?"0":"1";
                                        this.saveAccessPoint(row);
                                        this.forceUpdate();
                                    })
                                ,
                                Column.text("groups", "Grupy dostępu")
                                    .onClick((row, column, event, container) => {
                                        if (event.target.tagName == "DIV" || event.target.tagName == "TD" || event.target.tagName == "I") {
                                            row.edited = !row.edited;
                                        }
                                        container.forceUpdate();

                                    })
                                    .template((val, row, event, container) => {
                                        let v = parseInt(val || 0);
                                        let selected = [];
                                        let selectedNames = [];
                                        Object.entries(this.props.agroups).map(([id, name]) => {
                                            if (v & id) {
                                                selected.push(id);
                                                selectedNames.push(name);
                                            }
                                        })
                                        if (row.edited) {
                                            return <div> {container}
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
                                            </div>
                                        } else {


                                            return <div>{selectedNames.length > 0 ? selectedNames.join(", ") : <div className={"center"}><i className="fa fa-times  lightgrey"></i></div>}</div>
                                        }
                                    })
                                ,
                                Column.template("Zobacz", () => <i className="fa fa-times"></i>)
                                    .onClick(this.handleDelete.bind(this))
                                    .className("center darkred")
                            ]}
                        />
                    </Panel>
                </Row>
            </div>
        )
    }
}

/*    <table name="access_points_groups" class="Arrow\Access\Models\AccessPointGroup">
        <trackers>
            <tracker class="Arrow\Common\Models\Track\ORMObjectsTracker"/>
            <tracker class="Arrow\Common\Models\Track\ORMObjectsArchiver"/>
        </trackers>
        <field name="id" type="INTEGER" primaryKey="true" autoIncrement="true"/>
        <field name="access_points_id" type="INTEGER"/>
        <field name="group_id" type="INTEGER" size="255"/>
        <field name="policy" type="INTEGER"/>
    </table>*/
