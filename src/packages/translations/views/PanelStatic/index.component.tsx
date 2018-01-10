import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";

import {Table, Column} from "frontend/src/ctrl/Table";
import {Modal, confirm} from "frontend/src/ctrl/Overlays";
import {BFile, BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";

import {CommandBar} from "frontend/src/ctrl/CommandBar";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {Icon} from "frontend/src/ctrl/Icon";

interface IProps extends IArrowViewComponentProps {
    language: any;
    search: string;
    langToDownload: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    public table: any;
    private columns: any;

    constructor(props) {
        super(props);
        this.state = {
            langToDownload: false,
            search: "",
        };

        this.columns = [
            Column.id("id", "Id"),
            Column.text("lang", "Kod języka"),
            Column.map("lang", "Język", this.props.language),
            Column.text("value", "Wartość")
                .template((val, row, ) => <div>
                    {row.loading && <div><i className="fa fa-spinner fa-spin"/></div>}
                    {row.edited === true && [
                        <textarea style={{width: "100%", display: "block"}} onChange={(e) => row.changedText = e.target.value} defaultValue={val}/>,
                        <div>
                            <a onClick={this.handleRowChanged.bind(this, row)} className="btn btn-primary btn-xs btn-block pull-left" style={{margin: 0, width: "50%"}}>Zapisz</a>
                            <a onClick={(e) => {
                                e.stopPropagation();
                                row.edited = false;
                                this.table.forceUpdate();
                            }} className="btn btn-default btn-xs btn-block pull-right" style={{margin: 0, width: "50%"}}>Anuluj</a>
                        </div>,
                    ]}
                    {!row.loading && !row.edited && <div>{val}</div>}
                </div>)
                .set({styleTemplate: (row) => row.edited ? {padding: 0} : {}})
                .onClick((row, column, event, rowContainer) => {
                    row.edited = true;
                    row.changedText = row.value;
                    rowContainer.forceUpdate();
                })
            ,
            Column.text("original", "Orginał"),
            Column.text("module", "Moduł"),

            Column.template("", () => <i className="ms-Icon ms-Icon--Delete" style={{fontSize: 14}}/>)
                .className("center darkred")
                .onClick((row) => this.handleDelete(row)),
        ];

    }

    public handleRowChanged(row, e) {
        e.stopPropagation();
        row.loading = true;
        row.edited = false;
        this.table.forceUpdate();
        Comm._post(this.props.baseURL + "/inlineUpdate", {key: row.id, newValue: row.changedText}).then(() => {
            this.props._notification("Pomyślnie zmodyfikowano element");
            row.value = row.changedText;
            row.loading = false;
            this.table.forceUpdate();
        });

    }

    public handleDelete(row) {
        confirm(`Czy na pewno usunąć "${row.name}"?`).then(() => {
            Comm._post(this.props.baseURL + "/Language/delete", {key: row.id}).then(() => {
                this.props._notification(`Pomyślnie usunięto "${row.name}"`);
                this.table.load();
            });
        });
    }

    public handleUpload() {
        alert("Import w trakcie przygotowania");
    }

    public render() {
        const s = this.state;

        return (
            <div>
                <CommandBar
                    isSearchBoxVisible={true}
                    onSearch={(val) => {
                        this.setState({search: val}, () => this.table.load());
                    }}
                    items={[
                        {key: "f1", label: "Pobierz arkusz", icon: "Download", onClick: () => this.setState({langToDownload: "xx"})},
                        {key: "f2", label: "Załaduj plik", icon: "Upload", onClick: () => this.setState({isUploading: -1})},
                    ]}
                />
                <Navbar>
                    <span>Cms</span>
                    <span>Lista dostępnych tłumaczeń</span>
                </Navbar>
                <div className="panel-body-margins">
                    <Table
                        columns={this.columns}
                        remoteURL={this.props.baseURL + "/list"}
                        ref={(table) => this.table = table}
                        additionalConditions={{search: this.state.search}}
                    />
                </div>

                <Modal
                    title={"Pobranie pliku języka"}
                    show={s.langToDownload != false}

                    onHide={() => this.setState({langToDownload: false})}
                    showHideLink={true}
                    top={100}
                >
                    <div style={{padding: 10, maxWidth: 500}} className="container">
                        <BSelect
                            label={"Język do pobrania"}
                            value={this.state.langToDownload}
                            options={{xx: "--Wybierz język ---", ...this.props.language}}
                            onChange={(e) => this.setState({langToDownload: e.value})}
                        />

                        {this.state.langToDownload != "xx" && <button className="btn btn-primary pull-right"><Icon name={"Download"}/> Pobierz</button>}

                    </div>
                </Modal>

                <Modal
                    title={"Załaduj plik językowy"}
                    show={s.isUploading != false}
                    onHide={() => this.setState({isUploading: false, fileToUpload: false})}
                    showHideLink={true}
                    top={100}

                >
                    <div style={{padding: 10, maxWidth: 500}} className="container">
                        <BFile label={""} value={this.state.fileToUpload} onChange={(e) => this.setState({fileToUpload: e.value})}/>
                        {this.state.fileToUpload != false &&
                        <button onClick={this.handleUpload.bind(this)} className="btn btn-primary pull-right"><Icon name={"Upload"}/> Laduj</button>
                        }
                    </div>

                </Modal>

            </div>
        );
    }
}

