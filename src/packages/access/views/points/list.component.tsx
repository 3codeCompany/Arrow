import * as React from "react";

import { Navbar } from "serenity-controls/lib/Navbar";
import { Column, Table } from "serenity-controls/lib/Table";
import { Icon } from "serenity-controls/lib/Icon";

import { Comm } from "serenity-controls/lib/lib";
import { CheckboxGroup } from "serenity-controls/lib/fields";
import { CommandBar } from "serenity-controls/lib/CommandBar";
import { IArrowViewComponentProps } from "serenity-controls/lib/backoffice";

import { FilterHelper } from "serenity-controls/lib/filters";
import { fI18n } from "serenity-controls/lib/lib/I18n";
import { confirmDialog } from "serenity-controls/lib/ConfirmDialog";
import { PrintJSON } from "serenity-controls/lib/PrintJSON";

interface Props extends IArrowViewComponentProps {
    agroups: { [key: string]: string };
}

export default class extends React.Component<Props, any> {
    public table: any;

    constructor(props) {
        super(props);
        this.state = {
            selectedPoints: [],
        };
    }

    public handleDelete = () => {
        const length = this.state.selectedPoints.length;
        confirmDialog(`Czy na pewno usunąć ${length} punktów ?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", { keys: this.state.selectedPoints.map((el) => el.id) }).then(
                () => {
                    this.props._notification(`Usuneto  ${length} punkty dostępu.`);
                    this.table.load();
                },
            );
        });
    };

    public saveAccessPoint(row) {
        Comm._post(this.props._baseURL + "/save", {
            key: row.id,
            data: { groups: row.groups, control_enabled: row.control_enabled },
        }).then(() => {
            this.props._notification(`Punkt  "${row.point_object_friendly_id}" został zaktualizowany.`);
            // this.table.load();
        });
    }

    public render() {
        const routeFilterContent = [
            { value: "exists", label: "In route" },
            { value: "notExists", label: "Not in Route" },
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
                        onSelectionChange={(selection) => this.setState({ selectedPoints: selection })}
                        columns={[
                            Column.id("id", "Id").addFilter(
                                FilterHelper.switch("existsInRoute", "Exists in route", routeFilterContent).get(),
                            ),
                            Column.text("point_object_friendly_id", "Nazwa"),
                            Column.bool("control_enabled", "Kontrola").onClick((row, val, rowComponent) => {
                                row.control_enabled = row.control_enabled == "1" ? "0" : "1";
                                this.saveAccessPoint(row);
                                rowComponent.forceUpdate();
                            }),

                            Column.text("groups", "Grupy dostępu")
                                .onClick((row, column, rowComponent) => {
                                    row.edited = !row.edited;
                                    row.tmp_groups = row.groups;
                                    rowComponent.forceUpdate();
                                })
                                .template((val: any, row, column, rowComponent) => {
                                    //const v: number = parseInt(row.tmp_groups || 0, 10);
                                    const v: number = BigInt(row.tmp_groups !== undefined ? row.tmp_groups : 0);

                                    const selected = [];
                                    Object.entries(this.props.agroups).map(([id, name]) => {
                                        if (v & (BigInt(id) as any)) {

                                            selected.push(id);
                                        }
                                    });
                                    if (row.edited) {
                                        return (
                                            <div
                                                style={{ backgroundColor: "white", padding: 5 }}
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                <CheckboxGroup
                                                    value={selected}
                                                    options={Object.entries(this.props.agroups).map(
                                                        ([key, value]: any) => ({ value: parseInt(key), label: value }),
                                                    )}
                                                    onChange={(x) => {
                                                        row.tmp_groups = x.value.reduce(
                                                            (a: number, b: number) => parseInt(a) + parseInt(b),
                                                            0,
                                                        );
                                                        rowComponent.forceUpdate();
                                                    }}
                                                    selectTools={true}
                                                />
                                                <hr />
                                                <div style={{ display: "flex" }}>
                                                    <a
                                                        className="btn btn-primary"
                                                        style={{ width: "100%", textAlign: "center" }}
                                                        onClick={() => {
                                                            row.groups = row.tmp_groups;
                                                            row.edited = !row.edited;
                                                            this.saveAccessPoint(row);
                                                            rowComponent.forceUpdate();
                                                        }}
                                                    >
                                                        Ok
                                                    </a>
                                                    <a
                                                        className="btn btn-default"
                                                        style={{ width: "100%", textAlign: "center" }}
                                                        onClick={() => {
                                                            row.edited = !row.edited;
                                                            rowComponent.forceUpdate();
                                                        }}
                                                    >
                                                        Cancel
                                                    </a>
                                                </div>
                                            </div>
                                        );
                                    } else {
                                        const v: number = BigInt(row.groups || 0 );
                                        const selectedNames = [];
                                        Object.entries(this.props.agroups).map(([id, name]) => {
                                            if (v & (BigInt(id) as any)) {
                                                selectedNames.push(name);
                                            }
                                        });

                                        return (
                                            <div style={{ padding: 5 }}>
                                                {selectedNames.length > 0 ? (
                                                    selectedNames.join(", ")
                                                ) : (
                                                    <div className={"left"} style={{ color: "lightgrey" }}>
                                                        <Icon name={"ChromeClose"} />
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    }
                                })
                                .styleTemplate((row) => {
                                    return { padding: 0 };
                                })
                                .noFilter()
                                .width(500)
                                .addFilter(
                                    FilterHelper.select(
                                        "groups",
                                        "Grupy",
                                        Object.entries(this.props.agroups).map(([key, value]: any) => ({
                                            value: parseInt(key),
                                            label: value,
                                        })),
                                        true,
                                    )
                                        .setModalProperties({ width: 800 })
                                        .get(),
                                ),
                        ]}
                    />
                </div>
            </div>
        );
    }
}
