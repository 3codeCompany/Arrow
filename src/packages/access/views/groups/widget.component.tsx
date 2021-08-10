import * as React from "react";

import { CommonIcons } from "serenity-controls/lib/lib/CommonIcons";
import { Comm } from "serenity-controls/lib/lib";
import { confirmDialog } from "serenity-controls/lib/ConfirmDialog";

import { BCheckboxGroup, BSelect } from "serenity-controls/lib/BForm";

export default class ArrowViewComponent extends React.Component<any, any> {
    public static defaultProps = {
        presentation: false,
    };

    constructor(props) {
        super(props);

        const value = Object.entries(this.props.agroups)
            .map(([key, value]: any) => key)
            .filter((el) => BigInt(el) & BigInt(this.props.mask));

        this.state = {
            mask: BigInt(props.mask),
            owner: props.owner,
            value,
        };

        console.log(this.state.mask, "mask");
    }

    private handleSubmit = () => {
        this.props.onConfirm(this.state.value, this.state.mask, this.state.owner);
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

    private editAccessRights = (mask: number, owner: number) => {
        this.props._openModal(
            "/access/groups/widget/" + mask + "/" + owner,
            {},
            {
                width: 600,
                title: "Wybierz grupy dostępu",
            },
            {
                onConfirm: (value: number[], changedMask: number, owner: number) => {
                    this.setState(
                        {
                            value,
                            owner,
                        },
                        () => {
                            console.log("zmieniam stan");
                            if (this.props.onConfirm) {
                                this.props.onConfirm(value, changedMask, owner);
                            }
                            this.props._closeModal("/access/groups/widget/" + this.props.mask + "/" + this.props.owner);
                        },

                    );
                },
                onCancel: () => {
                    this.props._closeModal("/access/groups/widget/" + this.props.mask + "/" + this.props.owner);
                },
            },
        );
    };

    public render() {
        return (
            <div style={{ backgroundColor: "white", padding: 5 }}>
                <BSelect
                    label="Właściciel"
                    editable={!this.props.presentation}
                    options={this.props.owners.map((user) => ({ value: user.id, label: user.login }))}
                    value={this.state.owner}
                    onChange={(e) => this.setState({ owner: e.value })}
                />
                <hr />

                <BCheckboxGroup
                    label="Grupy"
                    editable={!this.props.presentation}
                    columnsCount={2}
                    columns={"vertical"}
                    options={Object.entries(this.props.agroups).map(([key, value]: any) => ({
                        value: key,
                        label: value,
                    }))}
                    onChange={(x) => {
                        const reduced = x.value.reduce((a: number, b: number) => BigInt(a) + BigInt(b), 0);
                        this.setState({
                            value: x.value,
                            mask: reduced,
                        });
                    }}
                    selectTools={true}
                    value={this.state.value}
                />

                {!this.props.presentation ? (
                    <>
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
                    </>
                ) : (
                    <a
                        onClick={() => this.editAccessRights(this.props.mask, this.props.owner)}
                        className="btn btn-primary"
                        style={{ marginTop: 10 }}
                    >
                        <Icon name="Unlock" /> Zmień uprawnienia
                    </a>
                )}
            </div>
        );
    }
}
