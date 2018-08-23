import * as React from "react";
import Navbar from "frontend/src/ctrl/Navbar";
import { Column, Table } from "frontend/src/ctrl/Table";

import { confirm, Modal } from "frontend/src/ctrl/Overlays";
import { BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig } from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";
import { Row } from "frontend/src/layout/BootstrapLayout";

import { IArrowViewComponentProps } from "frontend/src/lib/PanelComponentLoader";
import { CommandBar } from "frontend/src/ctrl/CommandBar";
import { Datasource } from "frontend/src/lib/Datasource";
import { LoaderContainer } from "frontend/src/ctrl/LoaderContainer";
import Icon from "frontend/src/ctrl/Icon";
import { FilterHelper } from "frontend/src/ctrl/filters/FilterHelper";

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
            filters: {},
        };

        this.columns = [
            Column.text("name", __("Nazwa")).template((val, row) => (
                <div style={{ marginLeft: 30 * (row.depth - 1) }}>
                    {row.type == "folder" ? <Icon name={"OpenFolderHorizontal"} /> : <Icon name={"FileASPX"} />}{" "}
                    {val}{" "}
                </div>
            )),
            Column.id("id", "Id").noFilter(),
            Column.text("country", __("Kraj"))
                .width(70)
                .className("center"),
            Column.bool("active", __("Aktywna")),
            Column.text("type", __("Typ"))
                .noFilter()
                .addFilter(
                    FilterHelper.select(
                        "type",
                        __("Typ"),
                        [
                            { value: "page", label: "page" },
                            { value: "folder", label: "folder" },
                            { value: "link", label: "link" }
                        ],
                        false
                    ).get()
                ),
            Column.text("link", __("Link")),

            Column.template("", () => {
                return <Icon name={"OpenInNewWindow"} />;
            })
                .className("center")
                .onClick((row) => {
                    window.open(`https://www.esotiq.com/${this.props.language}/${this.props.language}/${row.link}`);
                }),

            props.editEnabled
                ? Column.template("", () => <Icon name={"ChevronDown"} />)
                      .className("center")
                      .onClick((row) => {
                          Comm._post(this.props._baseURL + "/moveDown", { key: row.id }).then((result) => {
                              this.table.load();
                          });
                      })
                : null,
            props.editEnabled
                ? Column.template("", () => <Icon name={"ChevronUp"} />)
                      .className("center")
                      .onClick((row) => {
                          Comm._post(this.props._baseURL + "/moveUp", { key: row.id }).then((result) => {
                              this.table.load();
                          });
                      })
                : null,

            Column.template("", () => <Icon name={"Edit"} />)
                .className("center")
                .onClick((row) => {
                    this.props._goto(this.props._baseURL + "/edit", { key: row.id });
                }),

            props.editEnabled
                ? Column.template("", () => <Icon name={"Delete"} />)
                      .className("center darkred")
                      .onClick((row) => this.handleDelete(row))
                : null,
            Column.hidden("depth")
        ];
    }

    public handleDelete(row) {
        confirm(__("Czy na pewno usunąć") + ` "${row.name}"?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", { key: row.id }).then(() => {
                this.props._notification(__("Pomyślnie usunięto") + ` "${row.name}"`);
                this.table.load();
            });
        });
    }

    public loadObjectData() {
        if (this.state.currEdited != -1) {
            this.setState({ loading: true });
            Comm._post(this.props._baseURL + "/get", { key: this.state.currEdited }).then((response) =>
                this.setState({ currEditedData: response, loading: false })
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
                    items={[
                        {
                            key: "f1",
                            label: __("Dodaj") + "",
                            icon: "Add",
                            onClick: () => this.setState({ currEdited: -1 })
                        }
                    ]}
                />
                <Navbar>
                    <span>{__("CMS")}</span>
                    <span>{__("Strony wwww")}</span>
                </Navbar>
                <div style={{ padding: "0 10px" }}>
                    <Table
                        columns={this.columns}
                        remoteURL={this.props._baseURL + "/asyncIndex"}
                        ref={(table) => (this.table = table)}
                        filters={this.state.filters}
                        rememberState={true}
                        onFiltersChange={(filters) => {
                            this.setState({ filters })
                        }}
                    />
                </div>

                <Modal
                    title={(s.currEdited == -1 ? __("Dodanie") : __("Edycja")) + __(" strony www")}
                    show={s.currEdited != false}
                    onHide={() => this.setState({ currEdited: false })}
                    showHideLink={true}
                >
                    <BForm
                        data={{
                            type: "page"
                        }}
                        action={this.props._baseURL + "/add"}
                        namespace={"data"}
                        onSuccess={(e) => {
                            this.props._notification(this.state.currEditedData.name, __("Zapisano pomyślnie"));
                            this.setState({ currEdited: -1 });
                            this.table.load();
                        }}
                    >
                        {(form) => (
                            <div style={{ padding: 10, width: 400 }} className="">
                                <Row noGutters={false}>
                                    <BText label={__("Nazwa")} {...form("name")} />
                                    <BSwitch
                                        label={__("Typ")}
                                        options={[
                                            { label: "Strona", value: "page" },
                                            { label: "Folder", value: "container" }
                                        ]}
                                        {...form("type", "page")}
                                    />
                                </Row>
                                <Row noGutters={false}>
                                    <BSelect
                                        label={__("Element nadrzędny")}
                                        options={this.props.containers.map((el) => ({value: el.id, label: el.name}))}
                                        {...form("parent_id")}
                                    />
                                </Row>

                                <div className="hr-line-dashed" />
                                <button
                                    onClick={() => this.setState({ currEdited: false })}
                                    className="btn btn-default pull-right"
                                >
                                    {__("Anuluj")}
                                </button>
                                <button className="btn btn-primary pull-right">{__("Zapisz")}</button>
                            </div>
                        )}
                    </BForm>
                </Modal>
            </div>
        );
    }
}
