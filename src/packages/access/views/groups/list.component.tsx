import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";

import { Column, Table } from "frontend/src/ctrl/Table/Table";
import Panel from "frontend/src/ctrl/Panel";
import { Icon } from "frontend/src/ctrl/Icon";
import { Row } from "frontend/src/layout/BootstrapLayout";
import Comm from "frontend/src/lib/Comm";
import { confirmDialog } from "frontend/src/ctrl/overlays/ConfirmDialog";

export default class extends React.Component<any, any> {
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
                <Navbar>
                    <span>System</span>
                    <span>Grupy dostępu</span>
                </Navbar>
                <Row>
                    <Panel
                        title="Lista grup dostępu systemu"
                        toolbar={[
                            <a key="f1" href={"#" + this.props._baseURL + "/edit"} className="btn btn-sm btn-primary">
                                <i className="fa fa-plus" />
                                Dodaj
                            </a>,
                        ]}
                    >
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
                    </Panel>
                </Row>
            </div>
        );
    }
}
