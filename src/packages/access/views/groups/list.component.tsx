import * as React from "react";

import { Navbar } from "frontend/lib/Navbar";

import { Column, Table } from "frontend/lib/Table";
import { Panel } from "frontend/lib/Panel";
import { Icon } from "frontend/lib/Icon";
import { Row } from "frontend/lib/Row";
import { Comm } from "frontend/lib/lib";
import { confirmDialog } from "frontend/lib/ConfirmDialog";
import { CommandBar } from "frontend/lib/CommandBar";

export default class ArrowViewComponent extends React.Component<any, any> {
    public table: Table;

    constructor(props) {
        super(props);
        this.state = {};
    }

    public handleDelete(row, event) {
        confirmDialog(`Czy na pewno usunąć "${row.name}" ?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", { key: row.id }).then(() => {
                this.props._notification(`Grupa  "${row.name}" została usunięta.`);
                this.table.load();
            });
        });
    }

    public render() {
        return (
            <div>
                <CommandBar
                    items={[
                        {
                            key: "f1",
                            label: "Dodaj",
                            icon: "Add",
                            onClick: () => this.props._goto(this.props._baseURL + "/edit"),
                        },
                    ]}
                />

                <Navbar>
                    <span>System</span>
                    <span>Grupy dostępu</span>
                </Navbar>

                <div className="panel-body-margins">
                    <Table
                        remoteURL={this.props._baseURL + "/getData"}
                        ref={(table) => (this.table = table)}
                        columns={[
                            Column.id("id", "Id"),
                            Column.text("name", "Nazwa"),
                            Column.template("Zobacz", () => <Icon name={"Edit"} />)
                                .onClick((row) => this.props._goto(this.props._baseURL + "/edit", { key: row.id }))
                                .className("center darkgreen"),
                            Column.template("", () => <Icon name={"Delete"} />)
                                .onClick(this.handleDelete.bind(this))
                                .className("center darkred"),
                        ]}
                    />
                </div>
            </div>
        );
    }
}
