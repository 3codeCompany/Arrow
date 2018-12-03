import * as React from "react";

import { Navbar } from "frontend/lib/Navbar";

import {Table, Column} from "frontend/lib/Table";

import {BFile, BFileListField, BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from "frontend/lib/BForm";
import {Comm} from "frontend/lib/lib";

import {CommandBar} from "frontend/lib/CommandBar";
import {IArrowViewComponentProps} from "frontend/lib/backoffice";
import {Icon} from "frontend/lib/Icon";
import { download } from "frontend/lib/Downloader";
import {fI18n} from "frontend/lib/lib/I18n";
import {Modal} from "frontend/lib/Modal";
import {confirmDialog} from "frontend/lib/ConfirmDialog";

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
            isUploading: false,
            historyModalVisible: false,
        };

        this.columns = [
            Column.id("id", "Id"),
            Column.text("lang", fI18n.t("Kod języka")),
            Column.map("lang", fI18n.t("Język"), this.props.language),
            Column.text("value", fI18n.t("Wartość"))
                .template((val, row) => {
                    return <div>
                        {row.loading && <div><i className="fa fa-spinner fa-spin"/></div>}
                        {row.edited === true && [
                            <textarea
                                style={{width: "100%", display: "block"}}
                                onChange={(e) => row.changedText = e.target.value} defaultValue={val}
                                autoFocus={true}
                                onClick={(e) => e.stopPropagation}
                            />,
                            <div>

                                <a onClick={this.handleRowChanged.bind(this, row)} className="btn btn-primary btn-xs btn-block pull-left" style={{margin: 0, width: "50%"}}>{fI18n.t("Zapisz")}</a>
                                <a onClick={(e) => {
                                    e.stopPropagation();
                                    row.edited = false;
                                    row.container.forceUpdate();
                                }} className="btn btn-default btn-xs btn-block pull-right" style={{margin: 0, width: "50%"}}>{fI18n.t("Anuluj")}</a>
                            </div>,
                        ]}
                        {!row.loading && !row.edited && <div>{val}</div>}
                    </div>
                })
                .set({styleTemplate: (row) => row.edited ? {padding: 0} : {}})
                .onClick((row, column, rowContainer) => {

                    row.edited = true;
                    row.changedText = row.value;
                    row.container = rowContainer
                    rowContainer.forceUpdate();
                })
            ,
            Column.text("original", fI18n.t("Orginał")),
            Column.text("module", fI18n.t("Moduł")),

            Column.template("", () => <i className="ms-Icon ms-Icon--Delete" style={{fontSize: 14}}/>)
                .className("center darkred")
                .onClick((row) => this.handleDelete(row)),
        ];

    }

    handleDownload(): any {
        const lang = this.state.langToDownload
        //window.open(this.props._basePath + this.props._baseURL + "/downloadLangFile?lang=" + lang);

        download(this.props._basePath + this.props._baseURL + "/downloadLangFile?lang=" + lang);
    }

    handleBackup(lang: string): any {
        //window.open(this.props._basePath + this.props._baseURL + "/downloadLangFile?lang=" + lang);

        download(this.props._basePath + this.props._baseURL + "/langBackUp?lang=" + lang);
    }

    public handleRowChanged(row, e) {
        e.stopPropagation();
        row.loading = true;
        row.edited = false;
        row.container.forceUpdate();
        Comm._post(this.props._baseURL + "/inlineUpdate", {key: row.id, newValue: row.changedText}).then(() => {
            this.props._notification("Pomyślnie zmodyfikowano element");
            row.value = row.changedText;
            row.loading = false;
            this.table.load();
        });

    }

    public handleDelete(row) {
        confirmDialog(`Czy na pewno usunąć "${row.name}"?`).then(() => {
            Comm._post(this.props._baseURL + "/Language/delete", {key: row.id}).then(() => {
                this.props._notification(`Pomyślnie usunięto "${row.name}"`);
                this.table.load();
            });
        });
    }

    public render() {
        const s = this.state;

        return (
            <div>
                {this.props.country == "pl" ?
                <CommandBar
                    isSearchBoxVisible={true}
                    onSearch={(val) => {
                        this.setState({search: val}, () => this.table.load());
                    }}
                    items={[
                        {key: "f1", label: "Pobierz arkusz", icon: "Download", onClick: () => this.setState({langToDownload: "xx"})},
                        {key: "f2", label: "Załaduj plik", icon: "Upload", onClick: () => this.setState({isUploading: -1})},
                        {key: "f3", label: "Historia tłumaczeń", icon: "History", onClick: () => this.setState({historyModalVisible: true})},
                    ]}
                /> : null }
                <Navbar>
                    <span>{fI18n.t("Cms")}</span>
                    <span>{fI18n.t("Lista dostępnych tłumaczeń")}</span>
                </Navbar>
                <div className="panel-body-margins">
                    <Table
                        columns={this.columns}
                        remoteURL={this.props._baseURL + "/list"}
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

                        {this.state.langToDownload != "xx" && <button className="btn btn-primary pull-right" onClick={() => this.handleDownload()}><Icon name={"Download"}/> Pobierz</button>}

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
                        <BForm
                            ref={(el) => this.form = el}
                            action={this.props._baseURL + `/uploadLangFile`}
                            namespace={"data"}
                            onSuccess={(el) => {
                                if (el.response.status == "done"){
                                    this.props._notification("Sukces", "Plik załadowano poprawnie.");
                                    this.handleBackup(el.form.state.data.language);
                                    console.log(el.response);
                                } else {
                                    this.props._notification("Błąd", "Wybierz język.", {level: "error"});
                                    console.log(el.response);
                                }
                            }}
                        >{(form) => {
                            return (
                                <div>
                                    <BSelect
                                        label={"Język do wczytania"}
                                        value={this.state.langToDownload}
                                        options={{xx: "--Wybierz język ---", ...this.props.language}}
                                        onChange={(e) => {
                                            this.setState({langToDownload: e.value});
                                            console.log(this.state.langToDownload);
                                            }
                                        }
                                        require={true}
                                        {...form("language")}
                                    />
                                    <BFileListField name="files" {...form("files")}/>
                                    <button className="btn btn-primary pull-right"><Icon name={"Upload"}/> Laduj</button>
                                </div>
                            )
                        }}
                        </BForm>
                    </div>

                </Modal>

                <Modal
                    title={"Historia tłumaczeń"}
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
                                Column.text("language", fI18n.t("Język")).width(70).className("center uppercase"),
                                Column.email("user", fI18n.t("Użytkownik")),
                                Column.date("date", fI18n.t("Data")),
                                Column.date("time", fI18n.t("Czas")),
                                Column.text("full_name", fI18n.t("Pobierz"))
                                    .template((value, row) => {
                                        return (
                                            <a href={this.props._basePath + `/data/translate_uploads/${row["full_name"]}`} target={"_blank"}>
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


