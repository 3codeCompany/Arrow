import * as React from "react";

import { Navbar } from "frontend/lib/Navbar";

import { Column, Table } from "frontend/lib/Table";
import { Panel } from "frontend/lib/Panel";
import { Icon } from "frontend/lib/Icon";
import { Row } from "frontend/lib/Row";
import { Comm } from "frontend/lib/lib";
import { confirmDialog } from "frontend/lib/ConfirmDialog";
import { CheckboxGroup } from "frontend/lib/fields";

export default class ArrowViewComponent extends React.Component<any, any> {
    public table: Table;

    constructor(props) {
        super(props);

        const value = Object.entries(this.props.agroups)
            .map(([key, value]: any) => key)
            .filter((el) => parseInt(el) & this.props.mask);

        this.state = {
            mask: 0,
            value,
        };
    }

    private handleSubmit = () => {
        this.props.onConfirm(this.state.value, this.state.mask);
    };
    private handleCancel = () => {
        this.props.onCancel();
    };

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
            <div style={{ backgroundColor: "white", padding: 5 }}>
                <CheckboxGroup
                    columnsCount={2}
                    columns={"vertical"}
                    value={this.state.value}
                    options={Object.entries(this.props.agroups).map(([key, value]: any) => ({
                        value: parseInt(key),
                        label: value,
                    }))}
                    onChange={(x) => {
                        const reduced = x.value.reduce((a: number, b: number) => parseInt(a) + parseInt(b), 0);
                        this.setState({
                            value: x.value,
                            mask: reduced,
                        });
                    }}
                    selectTools={true}
                />
                <hr />
                <div style={{ display: "flex" }}>
                    <a
                        className="btn btn-primary"
                        style={{ width: "100%", textAlign: "center" }}
                        onClick={this.handleSubmit}
                    >
                        Ok
                    </a>
                    <a
                        className="btn btn-default"
                        style={{ width: "100%", textAlign: "center" }}
                        onClick={this.handleCancel}
                    >
                        Cancel
                    </a>
                </div>
            </div>
        );
    }
}
