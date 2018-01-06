import * as React from "react";
import Navbar from "frontend/src/ctrl/Navbar";
import Panel from "frontend/src/ctrl/Panel";
import {Table, Column, ColumnHelper} from "frontend/src/ctrl/Table";
import {Modal, confirm} from "frontend/src/ctrl/Overlays";
import {BFile, BSelect, BSwitch} from "frontend/src/layout/BootstrapForm";

import download from "frontend/src/lib/Downloader";
import Comm from "frontend/src/lib/Comm";
import {Row} from "frontend/src/layout/BootstrapLayout";
import {Select} from "frontend/src/ctrl/Fields";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {Icon} from "frontend/src/ctrl/Icon";
import {CommandBar} from "frontend/src/ctrl/CommandBar";

interface IProps extends IArrowViewComponentProps {
    language: any;
    objects: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    private table: any;
    private columns: ColumnHelper[];

    constructor(props) {
        super(props);
        this.state = {
            langToDownload: false,
            downloadOnlyEmpty: 0,
            selected: [],
            isUploading: false,
            fileToUpload: false,
            selectedObject: props.objects[0],
        };

    }

    public handleDownload() {
        download(this.props.baseURL + "/downloadLangFile", {
            lang: this.state.langToDownload,
            onlyEmpty: this.state.downloadOnlyEmpty,
            model: this.state.selectedObject.value,
        });
    }

    public handleUpload() {
        alert("Import w trakcie przygotowania");
    }

    public handleDelete(rows) {
        confirm(`Czy na pewno usunąć "${rows.length}" elementów?`).then(() => {
            Comm._post(this.props.baseURL + "/delete", {keys: rows.reduce((p, c) => p.concat(c.id), [])}).then(() => {
                this.props._notification(`Pomyślnie usunięto "${rows.length}" elementów`);
                this.table.load();
            });
        });
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
            row.containerReference.forceUpdate();
        });

    }

    public handleModelChange(e) {
        this.setState({selectedObject: this.props.objects.filter(el => el.value == e.value)[0]}, () => this.table.load());

    }

    public render() {
        const s = this.state;

        this.columns = [
            Column.id("id", "Id"),
            Column.text("lang", "Język [kod]"),
            Column.map("lang", "Język", this.props.language),
            Column.text("field", "Pole"),
            this.state.selectedObject.label == "Cechy" ? Column.text("C:name", "Kategoria") : null,
            Column.text("E:name", "Orginał")
                .template((val, row) => row["E:" + row.field]),
            Column.text("value", "Wartość")
                .template((val, row) => <div>
                    {row.loading && <div><i className="fa fa-spinner fa-spin"/></div>}
                    {row.edited === true && [
                        <textarea
                            style={{width: "100%", display: "block"}}
                            onChange={(e) => row.changedText = e.target.value} defaultValue={val}
                            onClick={(e) =>{
                                e.stopPropagation();
                            }}
                        />,
                        <div>
                            <a onClick={this.handleRowChanged.bind(this, row)} className="btn btn-primary btn-xs btn-block pull-left" style={{margin: 0, width: "50%"}}>Zapisz</a>
                            <a onClick={(e) => {
                                e.stopPropagation();
                                row.edited = false;
                                row.containerReference.forceUpdate();
                            }} className="btn btn-default btn-xs btn-block pull-right" style={{margin: 0, width: "50%"}}>Anuluj</a>
                        </div>,
                    ]}
                    {!row.loading && !row.edited && <div>{val}</div>}
                </div>)
                .set({styleTemplate: (row) => row.edited ? {padding: 0} : {}})
                .onClick((row, column, event, rowContainer) => {
                    row.edited = true;
                    row.containerReference = rowContainer;
                    row.changedText = row.value;
                    rowContainer.forceUpdate();
                })
            ,

            //Column.text('original', 'Orginał'),
            //Column.text('module', 'Moduł'),

            Column.template("", () => <i className="ms-Icon ms-Icon--Delete"/>)
                .className("center darkred")
                .onClick((row) => this.handleDelete([row])),
        ];

        return (
            <div>
                <CommandBar
                    isSearchBoxVisible={true}
                    onSearch={(val) => {
                        this.setState({search: val}, () => this.table.load());
                    }}
                    items={[
                        {key: "f1", label: "Pobierz arkusz", icon: "Download", onClick: () => this.setState({langToDownload: "xx"})},
                        {key: "f2", label: "Załaduj plik", icon: "Upload", onClick: () => this.setState({isUploading: true})},
                    ]}
                />

                <Navbar>
                    <span>Cms</span>
                    <span>Tłumaczenia</span>
                    <span>Obiekty</span>
                </Navbar>


                <div className="panel-body-margins">
                    <div key={0} style={{display: "inline-block"}}>
                        <Select className={"form-control"} value={this.state.selectedObject.value} onChange={this.handleModelChange.bind(this)} options={this.props.objects}/>
                    </div>
                    {this.state.selected.length > 0 &&
                    <a key={1} className="btn btn-danger btn-sm" onClick={() => this.handleDelete(this.state.selected)}>
                        <i className="fa fa-file-excel-o"/> Usuń ( {this.state.selected.length} )
                    </a>}

                    <Table
                        additionalConditions={{model: this.state.selectedObject.value}}
                        columns={this.columns}
                        remoteURL={this.props.baseURL + "/list"}
                        ref={(table) => this.table = table}
                        selectable={false}
                        onSelectionChange={(selected) => this.setState({selected})}
                        rememberState={true}
                    />

                </div>

                <Modal
                    title={"Pobranie pliku języka"}
                    show={s.langToDownload != false}
                    onHide={() => this.setState({langToDownload: false})}
                    showHideLink={true}
                >
                    <div style={{padding: 10, maxWidth: 500}} className="container">
                        <BSelect
                            label={"Język do pobrania"}
                            value={this.state.langToDownload}
                            options={{xx: "--Wybierz język ---", ...this.props.language}}
                            onChange={(e) => this.setState({langToDownload: e.value})}
                        />

                        {this.state.langToDownload != "xx" && [
                            <BSwitch
                                label="Ściągni tylko nie uzupełnione wartości"
                                value={this.state.downloadOnlyEmpty} onChange={(e) => this.setState({downloadOnlyEmpty: e.value})}
                                options={{0: "Nie", 1: "Tak"}}
                            />,

                        ]}

                        {this.state.langToDownload != "xx" &&
                        <button onClick={this.handleDownload.bind(this)} className="btn btn-primary pull-right"><i className="fa fa-download"/> Pobierz</button>
                        }

                    </div>
                </Modal>

                <Modal
                    title={"Załaduj plik językowy"}
                    show={s.isUploading != false}
                    onHide={() => this.setState({isUploading: false, fileToUpload: false})}
                    showHideLink={true}

                >
                    <div style={{padding: 10, maxWidth: 500}} className="container">
                        <BFile label={""} value={this.state.fileToUpload} onChange={(e) => this.setState({fileToUpload: e.value})}/>
                        {this.state.fileToUpload != false &&
                        <button onClick={this.handleUpload.bind(this)} className="btn btn-primary pull-right"><i className="fa fa-upload"/> Laduj</button>
                        }
                    </div>

                </Modal>

            </div>
        );
    }
}

