import * as React from "react";
import Navbar from "frontend/src/ctrl/Navbar";
import {Column, Table} from "frontend/src/ctrl/Table";

import {confirm, Modal} from "frontend/src/ctrl/Overlays";
import {BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";
import {Row} from "frontend/src/layout/BootstrapLayout";

import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {CommandBar} from "frontend/src/ctrl/CommandBar";
import {Datasource} from "frontend/src/lib/Datasource";
import {LoaderContainer} from "frontend/src/ctrl/LoaderContainer";
import Icon from "frontend/src/ctrl/Icon";

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
            Column.text("name", fI18n.t("Nazwa"))
                .template((val, row) => <div style={{marginLeft: 30 * (row.depth - 1)}}>{row.type == "container" ? <Icon name={"OpenFolderHorizontal"} /> : <Icon name={"FileASPX"} />} {val} </div>),
            Column.id("id", "Id").noFilter(),
            Column.bool("active", fI18n.t("Aktywna")),
            Column.map("type", fI18n.t("Typ"), {page: fI18n.t("Strona"), container: fI18n.t("Folder")}),
            Column.text("link", fI18n.t("Link")),

            props.editEnabled ? Column.template("", () => <Icon name={"ChevronDown"} />)
                .className("center")
                .onClick((row) => {
                    Comm._post(this.props._baseURL + "/moveDown", {key: row.id}).then((result) => {
                       this.table.load();
                    });
                }) : null,
            props.editEnabled ? Column.template("", () => <Icon name={"ChevronUp"} />)
                .className("center")
                .onClick((row) => {
                    Comm._post(this.props._baseURL + "/moveUp", {key: row.id}).then((result) => {
                       this.table.load();
                    });
                }) : null,

            Column.template("", () => <Icon name={"Edit"} /> )
                .className("center")
                .onClick((row) => {
                    this.props._goto(this.props._baseURL + "/edit", {key: row.id});
                }),

            props.editEnabled ? Column.template("", () => <Icon name={"Delete"} /> )
                .className("center darkred")
                .onClick((row) => this.handleDelete(row)) : null,
            Column.hidden("depth"),
        ];
    }

    public handleDelete(row) {
        confirm(fI18n.t("Czy na pewno usunąć") + ` "${row.name}"?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", {key: row.id}).then(() => {
                this.props._notification(fI18n.t("Pomyślnie usunięto") + ` "${row.name}"`);
                this.table.load();
            });
        });
    }

    public loadObjectData() {

        if (this.state.currEdited != -1) {
            this.setState({loading: true});
            Comm._post(this.props._baseURL + "/get", {key: this.state.currEdited})
                .then((response) => this.setState({currEditedData: response, loading: false}));

        } else {
            this.setState({currEditedData: {}});
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
                        {key: "f1", label: fI18n.t("Dodaj") +
                            "", icon: "Add", onClick: () => this.setState({currEdited: -1})},
                    ]}/>
                <Navbar>
                    <span>Cms</span>
                    <span>{fI18n.t("Strony www")}</span>
                </Navbar>
                <div style={{padding: "0 10px"}}>
                    <Table
                        columns={this.columns}
                        remoteURL={this.props._baseURL + "/asyncIndex"}
                        ref={(table) => this.table = table}
                    />
                </div>

                <Modal
                    title={(s.currEdited == -1 ? fI18n.t("Dodanie") : fI18n.t("Edycja")) + fI18n.t(" strony www")}
                    show={s.currEdited != false}
                    onHide={() => this.setState({currEdited: false})}
                    showHideLink={true}
                >

                    <BForm
                        data={[]}
                        action={this.props._baseURL + "/add"}
                        namespace={"data"}
                        onSuccess={(e) => {
                            this.props._notification(this.state.currEditedData.name, fI18n.t("Zapisano pomyślnie"));
                            this.setState({currEdited: -1});
                            this.table.load();

                        }}
                    >
                        {(form) => <div style={{padding: 10, width: 300}} className="">

                            <Row noGutters={false} md={[10, 2]}>
                                <BText label={fI18n.t("Nazwa")} {...form("name")} />

                            </Row>

                            <div className="hr-line-dashed"/>
                            <button onClick={() => this.setState({currEdited: false})} className="btn btn-default pull-right">{fI18n.t("Anuluj")}</button>
                            <button className="btn btn-primary pull-right">{fI18n.t("Zapisz")}</button>

                        </div>}
                    </BForm>
                </Modal>

            </div>
        );
    }
}
