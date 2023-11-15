import * as React from "react";
import { Navbar } from "serenity-controls/lib/Navbar";
import { Column, Table } from "serenity-controls/lib/Table";

import { confirm, Modal } from "serenity-controls/lib/ctrl/Overlays";
import { BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig } from "serenity-controls/lib/BForm";
import { Comm } from "serenity-controls/lib/lib";
import { Row } from "serenity-controls/lib/Row";

import { IArrowViewComponentProps } from "serenity-controls/lib/backoffice";
import { CommandBar } from "serenity-controls/lib/CommandBar";
import { Datasource } from "serenity-controls/lib/lib/Datasource";
import { LoaderContainer } from "serenity-controls/lib/ctrl/LoaderContainer";
import { CommonIcons } from "serenity-controls/lib/lib/CommonIcons";
import { trans } from "../../../translations/front/trans";

interface IProps extends IArrowViewComponentProps {
    groups: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    private columns: Column[];
    private table: Table;

    constructor(props) {
        super(props);
        this.state = {
            currEdited: false,
            dataLoading: false,
            currEditedData: {},
        };


        this.columns = [
            Column.text("name", trans("Nazwa")).template((val, row) => (
                <div style={{ marginLeft: 30 * (row.depth - 1) }}>
                    {row.type == "container" ? <CommonIcons.folder />: <CommonIcons.document />} {val}{" "}
                </div>
            )),
            Column.id("id", "Id").noFilter(),
            Column.bool("active", trans("Aktywna")),
            Column.map("type", trans("Typ"), { page: trans("Strona"), container: trans("Folder") }),
            Column.text("link", trans("Link")),

            props.editEnabled
                ? Column.template("", () => <CommonIcons.chevronDown />)
                      .className("center")
                      .onClick((row) => {
                          Comm._post(this.props._baseURL + "/moveDown", { key: row.id }).then((result) => {
                              this.table.load();
                          });
                      })
                : null,
            props.editEnabled
                ? Column.template("", () => <CommonIcons.chevronUp />)
                      .className("center")
                      .onClick((row) => {
                          Comm._post(this.props._baseURL + "/moveUp", { key: row.id }).then((result) => {
                              this.table.load();
                          });
                      })
                : null,

            Column.template("", () => <CommonIcons.edit />)
                .className("center")
                .onClick((row) => {
                    this.props._goto(this.props._baseURL + "/edit", { key: row.id });
                }),

            props.editEnabled
                ? Column.template("", () => <CommonIcons.delete />)
                      .className("center darkred")
                      .onClick((row) => this.handleDelete(row))
                : null,
            Column.hidden("depth"),
        ];
    }

    public handleDelete(row) {
        confirmDialog(trans("Czy na pewno usunąć") + ` "${row.name}"?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", { key: row.id }).then(() => {
                this.props._notification(trans("Pomyślnie usunięto") + ` "${row.name}"`);
                this.table.load();
            });
        });
    }

    public loadObjectData() {
        if (this.state.currEdited != -1) {
            this.setState({ loading: true });
            Comm._post(this.props._baseURL + "/get", { key: this.state.currEdited }).then((response) =>
                this.setState({ currEditedData: response, loading: false }),
            );
        } else {
            this.setState({ currEditedData: {} });
        }
    }

    public render() {
        const s = this.state;

        return (
            <div>
                <CommandBar
                    isSearchBoxVisible={false}
                    onSearch={(search) => alert("To implement " + search)}
                    items={[{ key: "f1", label: trans("Dodaj") + "", icon: "Add", onClick: () => this.setState({ currEdited: -1 }) }]}
                />
                <Navbar>
                    <span>Cms</span>
                    <span>{trans("Strony www")}</span>
                </Navbar>
                <div style={{ padding: "0 10px" }}>
                    <Table columns={this.columns} remoteURL={this.props._baseURL + "/asyncIndex"} ref={(table) => (this.table = table)} />
                </div>

                <Modal
                    title={(s.currEdited == -1 ? trans("Dodanie") : trans("Edycja")) + trans(" strony www")}
                    show={s.currEdited != false}
                    onHide={() => this.setState({ currEdited: false })}
                    showHideLink={true}
                >
                    <BForm
                        data={[]}
                        action={this.props._baseURL + "/add"}
                        namespace={"data"}
                        onSuccess={(e) => {
                            this.props._notification(this.state.currEditedData.name, trans("Zapisano pomyślnie"));
                            this.setState({ currEdited: -1 });
                            this.table.load();
                        }}
                    >
                        {(form) => (
                            <div style={{ padding: 10, width: 300 }} className="">
                                <Row noGutters={false} md={[10, 2]}>
                                    <BText label={trans("Nazwa")} {...form("name")} />
                                </Row>

                                <div className="hr-line-dashed" />
                                <button onClick={() => this.setState({ currEdited: false })} className="btn btn-default pull-right">
                                    {trans("Anuluj")}
                                </button>
                                <button className="btn btn-primary pull-right">{trans("Zapisz")}</button>
                            </div>
                        )}
                    </BForm>
                </Modal>
            </div>
        );
    }
}
