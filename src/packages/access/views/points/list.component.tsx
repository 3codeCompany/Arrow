import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import {confirm} from "frontend/src/ctrl/Overlays";
import {Column, Table} from "frontend/src/ctrl/Table";
import {Icon} from "frontend/src/ctrl/Icon";

import Comm from "frontend/src/lib/Comm";
import {CheckboxGroup} from "frontend/src/ctrl/Fields";
import {CommandBar} from "../../../../../../../../node_modules_shared/frontend/src/ctrl/CommandBar";
import {IArrowViewComponentProps} from "../../../../../../../../node_modules_shared/frontend/src/lib/PanelComponentLoader";
import {SwitchFilter} from "../../../../../../../../node_modules_shared/frontend/src/ctrl/Filters";
import {FilterHelper} from "../../../../../../../../node_modules_shared/frontend/src/ctrl/filters/FilterHelper";
import {fI18n} from "frontend/src/utils/I18n";

interface Props extends IArrowViewComponentProps {
    agroups: { [key: string]: string };
}

export default class extends React.Component<Props, any> {
    table: any;

    constructor(props) {
        super(props);
        this.state = {
            selectedPoints: [],
        };
    }

    public handleDelete = () => {
        const length = this.state.selectedPoints.length;
        confirm(`Czy na pewno usunąć ${length} punktów ?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", {keys: this.state.selectedPoints.map(el => el.id)}).then(() => {
                this.props._notification(`Usuneto  ${length} punkty dostępu.`);
                this.table.load();
            })
        });
    }

    public

    saveAccessPoint(row) {
        Comm._post(this.props._baseURL + "/save", {key: row.id, data: {groups: row.groups, control_enabled: row.control_enabled}}).then(() => {
            this.props._notification(`Punkt  "${row.point_object_friendly_id}" został zaktualizowany.`);
            //this.table.load();
        });
    }

    public

    render() {
        const routeFilterContent = [
            {value: "exists", label: "In route"},
            {value: "notExists", label: "Not in Route"}
        ];
        return (
            <div>
                <CommandBar
                    items={[
                        {
                            key: "f1",
                            label: fI18n.t("Synchronizuj struktórę uprawnień"),
                            icon: "Sync",
                            onClick: () => {
                                Comm._get(this.props._baseURL + "/sync-access-points");
                            },
                        },
                        this.state.selectedPoints.length > 0 && {
                            key: "f2",
                            label: `Usuń wybrane wpisy (${this.state.selectedPoints.length})`,
                            icon: "Delete",
                            onClick: this.handleDelete,
                        },
                    ]}
                />
                <Navbar>
                    <span>System</span>
                    <span>Usawienia dostępu</span>
                </Navbar>
                <div className="panel-body-margins">
                    <Table
                        remoteURL={this.props._baseURL + "/getData"}
                        ref={(table) => (this.table = table)}
                        rememberState={true}
                        selectable={true}
                        onSelectionChange={(selection) => this.setState({selectedPoints: selection})}
                        columns={[
                            Column.id("id", "Id")
                                .addFilter(FilterHelper.switch("existsInRoute", "Exists in route", routeFilterContent).get())
                            ,

                            Column.text("point_object_friendly_id", "Nazwa"),
                            Column.bool("control_enabled", "Kontrola").onClick((row, val, rowComponent) => {
                                row.control_enabled = row.control_enabled == "1" ? "0" : "1";
                                this.saveAccessPoint(row);
                                rowComponent.forceUpdate();
                            }),
                            Column.text("groups", "Grupy dostępu")
                                .onClick((row, column, rowComponent) => {
                                    row.edited = !row.edited;
                                    rowComponent.forceUpdate();
                                })
                                .template((val: any, row) => {
                                    const v: number = parseInt(val || 0, 10);
                                    const selected = [];
                                    const selectedNames = [];
                                    Object.entries(this.props.agroups).map(([id, name]) => {
                                        if (v & (id as any)) {
                                            selected.push(id);
                                            selectedNames.push(name);
                                        }
                                    });
                                    if (row.edited) {
                                        return (
                                            <div>
                                                <CheckboxGroup
                                                    value={selected}
                                                    options={this.props.agroups}
                                                    onChange={(x) => {
                                                        if (x.event.target.checked) {
                                                            row.groups = v + parseInt(x.event.target.value, 10);
                                                        } else {
                                                            row.groups = v - parseInt(x.event.target.value, 10);
                                                        }
                                                        this.saveAccessPoint(row);
                                                    }}
                                                />
                                            </div>
                                        );
                                    } else {
                                        return (
                                            <div>
                                                {selectedNames.length > 0 ? (
                                                    selectedNames.join(", ")
                                                ) : (
                                                    <div className={"left"} style={{color: "lightgrey"}}>
                                                        <Icon name={"ChromeClose"}/>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    }
                                }),
                        ]}
                    />
                </div>
            </div>
        );
    }
}
