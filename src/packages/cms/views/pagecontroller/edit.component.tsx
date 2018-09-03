import * as React from "react";
import Navbar from "frontend/src/ctrl/Navbar";
import {Column, Table} from "frontend/src/ctrl/Table";
import {FileList} from "frontend/src/ctrl/FileLists";
import {confirm} from "frontend/src/ctrl/Overlays";
import {BFileList, BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";
import {Row} from "frontend/src/layout/BootstrapLayout";

import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {CommandBar} from "frontend/src/ctrl/CommandBar";

import {Panel} from "frontend/src/ctrl/Panel";
import {TabPane, Tabs} from "frontend/src/ctrl/Tabs";
import {fI18n} from "frontend/src/utils/I18n";


interface IProps extends IArrowViewComponentProps {
    page: any;
    languages: any;
    parents: any;

}

interface IEditFormHOC {
    saveURL?: string;
    getDataURL?: string;
    backURL?: string;
}

const EditFormHOC = ({}: IEditFormHOC = {}) =>
    <TOriginalProps extends object>(Component: (React.ComponentClass<TOriginalProps> | React.StatelessComponent<TOriginalProps>)) => {
        type ResultProps = TOriginalProps & IArrowViewComponentProps;
        return class extends React.Component<ResultProps, any> {
            public static defaultProps: Partial<IArrowViewComponentProps> = {};

            public render(): JSX.Element {
                const props = this.props;
                return <div style={{display: "relative"}}>
                    <Component {...props}  />
                </div>;
            }
        };
    };

class ArrowViewComponent extends React.Component<IProps, any> {
    private columns: Column[];
    private table: Table;

    constructor(props) {
        super(props);

        let x;
        for ( let i = 0; i < props.languages.length; i++) {
            if (props.languages[i].code == props.language) {
                x = i;
                break;
            }
        }

        this.state = {
            page: {...props.page},
            language: props.language,
            indexTab: x,
            dirty: false,
        };

        this.props.languages.push({name: "All", code: "all", id: "14"});
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

    public handleLangChange(index, event) {
        this.setState({language: this.props.languages[index].code});
        this.props._reloadProps({language: this.props.languages[index].code});
    }

    public componentWillReceiveProps(nextProps) {
        this.setState({page: nextProps.page});
    }

    public handleSave() {

        this.props._startLoadingIndicator();

        const toSend = {
            page: this.state.page,
            language: this.state.language,
        };

        Comm._post(this.props._baseURL + "/save", toSend).then((response) => {
            this.props._reloadProps();
            this.setState({dirty: false});
            this.props._notification(toSend.page.name, fI18n.t("Zapisano pomyślnie"));
            this.props._stopLoadingIndicator();
        });
    }

    public render() {
        const s = this.state;
        const {languages, parents, contentTypes} = this.props;
        const page = this.state.page;

        return (
            <div>
                <CommandBar
                    isSearchBoxVisible={false}
                    items={[
                        {key: "f0", label: fI18n.t("Wróć"), icon: "Back", onClick: () => this.props._goto(this.props._baseURL )},
                        {key: "f1", label: fI18n.t("Zapisz") + ` [${this.state.language}]`, icon: "Save", onClick: () => this.handleSave()},
                        this.state.dirty ? {
                            key: "f3", label: fI18n.t("Anuluj") + ` `, icon: "Save", onClick: () => {
                                this.props._reloadProps();
                                this.setState({dirty: false});
                            },
                        } : null,

                    ]}/>

                <div className={"panel-body-margins"}>
                    <Navbar>
                        <span>{fI18n.t("CMS")}</span>
                        <a onClick={() => this.props._goto(this.props._baseURL + "")}>{fI18n.t("Strony www")}</a>
                        <span>{page.name}</span>
                    </Navbar>

                    <Row noGutters={true} md={[12]}>
                        <Tabs defaultActiveTab={this.state.indexTab} onTabChange={this.handleLangChange.bind(this)}>
                            {languages.map((el) => <TabPane title={el.name} key={el.id}>{null}</TabPane>)}
                        </Tabs>
                    </Row>

                    <div className="">
                        <BForm
                            data={s.page}
                            onChange={(form) => this.setState({page: form.form.getData(), dirty: true})}
                        >
                            {(form) => <Row>
                                <div>

                                    <Panel noPadding={true} title={fI18n.t("Dane")}>

                                        <Row noGutters={false}>
                                            <BText label={fI18n.t("Nazwa")} {...form("name")} />
                                            {this.props.editEnabled ?
                                                <BSelect label={fI18n.t("Kraj")} {...form("country")} options={languages.map((el) => ({value: el.code, label: el.name}))}/>
                                            : null }
                                        </Row>
                                        {this.props.editEnabled ?
                                            <Row noGutters={false}>
                                                <BSwitch label={fI18n.t("Aktywna")} {...form("active")} options={{0: "Nie", 1: "Tak"}}/>
                                                <BSwitch label={fI18n.t("Typ")} {...form("type")} options={{page: fI18n.t("Strona"), container: fI18n.t("Folder")}}/>
                                            </Row>
                                            : null
                                        }
                                        <Row noGutters={false}>
                                            {/*<BSelect label="Język" {...form("language")} options={languages.map((el) => ({value: el.id, label: el.name}))}/>*/}
                                            {this.props.editEnabled ?
                                                <BSelect label={fI18n.t("Element nadrzędny")} {...form("parent_id")} options={parents.map((el) => ({value: el.id, label: el.name}))}/>
                                                : null
                                            }

                                            <BText label={fI18n.t("Link")} {...form("link")} />
                                            <BSwitch label={fI18n.t("Aktywna")} options={[{value: 0, label: "Nie"}, {value: 1, label: "Tak"}]} {...form("active")} />
                                        </Row>
                                        {page.type == "page" && <Row noGutters={false}>
                                        {this.props.editEnabled ?
                                                <BSelect label={fI18n.t("Typ zawartości")} {...form("content_type")} options={contentTypes}/>
                                                : null
                                            }
                                        </Row>}
                                    </Panel>
                                    {page.type == "page" &&
                                    <Panel noPadding={true} title={fI18n.t("SEO")}>
                                        <Row noGutters={false}>
                                            <BText label={fI18n.t("SEO Title")} {...form("seo_title")} />
                                            <BText label={fI18n.t("SEO Keywords")} {...form("seo_keywords")} />
                                        </Row>
                                        <Row noGutters={false}>
                                            <BTextarea label={fI18n.t("SEO description")} {...form("seo_description")} />
                                        </Row>
                                        <Row noGutters={false}>
                                            <BTextarea label={fI18n.t("Dodatkowy tekst na stronie")} {...form("seo_page_text")} />
                                        </Row>

                                        <Row>
                                            <Panel noPadding={true} title={"Nagłówek"} icon={"FileImage"}>
                                                <Row noGutters={false}>
                                                    <BFileList {...form("files[header]")} type={"gallery"} maxLength={1}  />
                                                </Row>
                                            </Panel>
                                        </Row>
                                        <Row>
                                            <Panel noPadding={true} title={"Obrazy"} icon={"FileImage"}>
                                                <Row noGutters={false}>
                                                    <BFileList {...form("files[files]")} type={"gallery"}/>
                                                </Row>
                                            </Panel>
                                        </Row>
                                        <Row>
                                            <Panel noPadding={true} title={"Pozostałe pliki"} icon={"Files"}>
                                                <Row noGutters={false}>
                                                    <BFileList {...form("files[attachments]")} type={"filelist"}/>
                                                </Row>
                                            </Panel>
                                        </Row>
                                    </Panel>
                                    }
                                </div>
                                <div>
                                    {page.type != "folder" &&
                                    <Panel noPadding={true}>
                                        <BWysiwig label={""} {...form("content")} style={{height: 600}}/>
                                    </Panel>
                                    }
                                </div>
                            </Row>}
                        </BForm>
                    </div>
                </div>
            </div>
        );
    }
}

export default EditFormHOC()(ArrowViewComponent);
