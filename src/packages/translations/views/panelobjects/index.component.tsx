import * as React from "react";
import Navbar from "frontend/src/ctrl/Navbar";
import {Column, Table} from "frontend/src/ctrl/Table";
import {confirm, Modal} from "frontend/src/ctrl/Overlays";
import {BFile, BFileList, BForm, BSelect, BSwitch} from "frontend/src/layout/BootstrapForm";

import download from "frontend/src/lib/Downloader";
import Comm from "frontend/src/lib/Comm";
import {Select} from "frontend/src/ctrl/Fields";
import {translate} from "frontend/src/utils/Translator";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {Icon} from "frontend/src/ctrl/Icon";
import {CommandBar} from "frontend/src/ctrl/CommandBar";
import {FilterHelper} from "../../../../../../../../node_modules_shared/frontend/src/ctrl/filters/FilterHelper";

interface IProps extends IArrowViewComponentProps {
    language: any;
    objects: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    private table: any;
    private columns: Column[];

    constructor(props) {
        super(props);
        this.state = {
            langToDownload: false,
            downloadOnlyEmpty: 0,
            selected: [],
            isUploading: false,
            fileToUpload: false,
            selectedObject: props.objects[0],
            historyModalVisible: false,

            field: null,

            isTableEditable: false,
            tableRowContainer: null,
        };

    }

    public handleDownload() {
        download(this.props._baseURL + "/downloadLangFile", {
            lang: this.state.langToDownload,
            onlyEmpty: this.state.downloadOnlyEmpty,
            model: this.state.selectedObject.value,
        });
    }

    handleBackup(lang): any {
        //window.open(this.props._basePath + this.props._baseURL + "/downloadLangFile?lang=" + lang);
        download(this.props._baseURL + "/langBackUp", {
            lang: this.state.langToDownload,
            onlyEmpty: this.state.downloadOnlyEmpty,
            model: this.state.selectedObject.value,
        });
    }

    public handleUpload(files) {
        this.props._startLoadingIndicator();
        Comm._post(this.props._baseURL + "/uploadFile", {file: this.state.fileToUpload}).then(() => {
            this.props._notification(__("Pomyślnie załadowano plik"));
            this.props._stopLoadingIndicator();
        });
    }

    public handleDelete(rows) {
        confirm(`Czy na pewno usunąć "${rows.length}" elementów?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", {keys: rows.reduce((p, c) => p.concat(c.id), [])}).then(() => {
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
        Comm._post(this.props._baseURL + "/inlineUpdate", {key: row.id, newValue: row.changedText}).then(() => {
            this.props._notification(__("Pomyślnie zmodyfikowano element"));
            row.value = row.changedText;
            row.loading = false;
            row.containerReference.forceUpdate();
        });

    }

    public handleModelChange(e) {
        this.setState({selectedObject: this.props.objects.filter(el => el.value == e.value)[0]}, () => this.table.load());

    }

    public render() {
        let field = null;
        const s = this.state;

        this.columns = [
            this.state.selectedObject == "Arrow\\Shop\\Models\\Persistent\\Product" ? Column.hidden("E:name") : null,
            this.state.selectedObject == "Arrow\\Shop\\Models\\Persistent\\Product" ? Column.hidden("E:group_key") : null,
            this.state.selectedObject == "Arrow\\Shop\\Models\\Persistent\\Product" ? Column.hidden("E:color") : null,
            Column.id("id_object", "id obiektu tłumaczonego").width(200).className("center"),
            Column.id("id", "Id"),
            Column.text("lang", __("Język [kod]")).width(140),
            Column.map("lang", __("Język"), this.props.language),
            Column.text("field", __("Pole")),
            // this.state.selectedObject.label == "Cechy" ? Column.text("C:name", "Kategoria") : null,
            this.state.selectedObject.value == "Arrow\\Shop\\Models\\Persistent\\Product" ? Column.template("Nazwa", (value, row) => {
                return <div>
                    <div>{row["E:name"]} <a href={"https://www.esotiq.com/pl/pl/_/_-" + row.id_object} className="pull-right" target="_blank"><Icon name={"Share"}/></a></div>
                    <small>{row["E:group_key"]}-{row["E:color"]}</small>
                </div>;
            }).addFilter(FilterHelper.text("E:name", __("Nazwa")).get()) : null,
            Column.template("Orignał", (val, row) => {
                field = "E:" + row.field;
                this.setState({
                    field
                })
                this.table.load();
                return (
                    row["E:" + row.field]
                )
            }).width("30%").addFilter(FilterHelper.text(this.state.field, __("Oryginał")).get()),
            Column.text("value", __("Wartość"))
                .template((val, row) => <div>
                    {row.loading && <div><i className="fa fa-spinner fa-spin"/></div>}
                    {this.state.isTableEditable === row.id && <>
                        <textarea
                            style={{width: "100%", display: "block", minHeight: 80}}
                            onChange={(e) => row.changedText = e.target.value} defaultValue={val}
                            onClick={(e) => {
                                e.stopPropagation();
                            }}
                        />
                        <div>
                            <a onClick={this.handleRowChanged.bind(this, row)}
                               className="btn btn-primary btn-xs btn-block pull-left"
                               style={{margin: 0, width: "50%"}}>Zapisz</a>
                            <a onClick={(e) => {
                                this.setState({
                                    isTableEditable: null,
                                })
                                e.stopPropagation();
                                row.edited = false;
                                this.state.tableRowContainer.forceUpdate();
                            }} className="btn btn-default btn-xs btn-block pull-right"
                               style={{margin: 0, width: "50%"}}>Anuluj</a>
                        </div>
                    </>}
                    {this.state.isTableEditable !== row.id && <div>{val}</div>}
                </div>)
                .set({styleTemplate: (row) => row.edited ? {padding: 0} : {}})
                .onClick((row, column, rowContainer) => {
                    console.log(column);
                    this.setState({
                        isTableEditable: row.id,
                        tableRowContainer: rowContainer,
                    })
                    row.edited = true;
                    row.changedText = row.value;
                    rowContainer.forceUpdate();
                })
                .width("30%")
            ,

            //Column.text('original', 'Orginał'),
            //Column.text('module', 'Moduł'),

            /*Column.template("", () => <Icon name={"Dalete"} />)
                .className("center darkred")
                .onClick((row) => this.handleDelete([row])),*/
        ];

        return (
            <div>
                <CommandBar
                    items={[
                        {
                            key: "f1",
                            label: __("Pobierz arkusz"),
                            icon: "Download",
                            onClick: () => this.setState({langToDownload: "xx"})
                        },
                        {
                            key: "f2",
                            label: __("Załaduj plik"),
                            icon: "Upload",
                            onClick: () => this.setState({isUploading: true})
                        },
                        {key: "f3", label: __("Historia tłumaczeń"), icon: "History", onClick: () => this.setState({historyModalVisible: true})},
                    ]}
                />

                <Navbar>
                    <span>{__("Cms")}</span>
                    <span>{__("Tłumaczenia")}</span>
                    <span>{__("Obiekty")}</span>
                </Navbar>


                <div className="panel-body-margins">
                    <div key={0} style={{display: "inline-block"}}>
                        <Select className={"form-control"} value={this.state.selectedObject.value}
                                onChange={this.handleModelChange.bind(this)} options={this.props.objects}/>
                    </div>
                    {this.state.selected.length > 0 &&
                    <a key={1} className="btn btn-danger btn-sm" onClick={() => this.handleDelete(this.state.selected)}>
                        <i className="fa fa-file-excel-o"/> Usuń ( {this.state.selected.length} )
                    </a>}

                    <Table
                        additionalConditions={{model: this.state.selectedObject.value}}
                        columns={this.columns}
                        remoteURL={this.props._baseURL + "/list"}
                        ref={(table) => this.table = table}
                        selectable={false}
                        onSelectionChange={(selected) => this.setState({selected})}
                        rememberState={true}
                    />

                </div>

                <Modal
                    title={__("Pobranie pliku języka")}
                    show={s.langToDownload != false}
                    onHide={() => this.setState({langToDownload: false})}
                    showHideLink={true}
                >
                    <div style={{padding: 10, maxWidth: 500}} className="container">
                        <BSelect
                            label={__("Język do pobrania")}
                            value={this.state.langToDownload}
                            options={{xx: __("--Wybierz język ---"), ...this.props.language}}
                            onChange={(e) => this.setState({langToDownload: e.value})}
                        />

                        {this.state.langToDownload != "xx" && [
                            <BSwitch
                                label={__("Ściągni tylko nie uzupełnione wartości")}
                                value={this.state.downloadOnlyEmpty}
                                onChange={(e) => this.setState({downloadOnlyEmpty: e.value})}
                                options={{0: "Nie", 1: "Tak"}}
                            />,

                        ]}

                        {this.state.langToDownload != "xx" &&
                        <button onClick={this.handleDownload.bind(this)} className="btn btn-primary pull-right"><i
                            className="fa fa-download"/> Pobierz</button>
                        }

                    </div>
                </Modal>

                <Modal
                    title={__("Załaduj plik językowy")}
                    show={s.isUploading != false}
                    onHide={() => this.setState({isUploading: false, fileToUpload: false})}
                    showHideLink={true}
                    top={100}

                >
                    <div style={{padding: 10, maxWidth: 500}} className="container">
                        <BForm
                            ref={(el) => this.form = el}
                            action={this.props._baseURL + `/uploadFile`}
                            namespace={"data"}
                            onSuccess={(el) => {
                                if (el.response.status == "done"){
                                    this.props._notification(__("Sukces"), __("Plik załadowano poprawnie"));
                                    this.handleBackup(el.form.state.data.language);
                                } else {
                                    this.props._notification(__("Błąd"), __("Wybierz język"), {level: "error"});
                                }
                            }}
                        >{(form) => {
                            return (
                                <div>
                                    <BSelect
                                        label={__("Język do wczytania")}
                                        value={this.state.langToDownload}
                                        options={{xx: __("--Wybierz język ---"), ...this.props.language}}
                                        onChange={(e) => {
                                            this.setState({langToDownload: e.value});
                                        }
                                        }
                                        require={true}
                                        {...form("language")}
                                    />
                                    <BFileList name="files" {...form("files")}/>
                                    <button className="btn btn-primary pull-right"><Icon name={"Upload"}/> {__("Laduj")}</button>
                                </div>
                            )
                        }}
                        </BForm>
                    </div>

                </Modal>

                <Modal
                    title={__("Historia tłumaczeń")}
                    show={this.state.historyModalVisible}
                    onHide={() => this.setState({historyModalVisible: false})}
                    showHideLink={true}
                    top={100}

                >
                    <div style={{padding: 10}} className="container">
                        <Table
                            remoteURL={this.props._baseURL + `/history`}
                            onPage={100}
                            showFooter={false}
                            columns={[
                                Column.text("language", __("Język")).width(70).className("center uppercase"),
                                Column.email("user", __("Użytkownik")),
                                Column.date("date", __("Data")),
                                Column.date("time", __("Czas")),
                                Column.text("full_name", __("Pobierz"))
                                    .template((value, row) => {
                                        return (
                                            <a href={this.props._basePath + `/data/translate_object_uploads/${row["full_name"]}`} target={"_blank"}>
                                                <Icon name={"Download"}/>
                                            </a>
                                        );
                                    }).width(60).className("center").noFilter(true)
                            ]}
                        />
                    </div>

                </Modal>
            </div>
        );
    }
}

