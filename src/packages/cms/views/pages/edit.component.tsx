import * as React from "react";
import { Navbar } from "frontend/lib/Navbar";
import {Column, Table} from "frontend/lib/Table";
import {BFileListField, BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from "frontend/lib/BForm";
import {Comm} from "frontend/lib/lib";
import {Row} from "frontend/lib/Row";

import {IArrowViewComponentProps} from "frontend/lib/backoffice";
import {CommandBar} from "frontend/lib/CommandBar";
import {Arrow} from "../../../../data/cache/db/ts-definitions";
import {Panel} from "frontend/lib/Panel";
import {TabPane, Tabs} from "frontend/lib/Tabs";
import IPage = Arrow.CMS.Models.Persistent.IPage;
import {fI18n} from "frontend/lib/lib/I18n";
import {confirmDialog} from "frontend/lib/ConfirmDialog";

interface IProps extends IArrowViewComponentProps {
    page: IPage;
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

    }

    public handleDelete(row) {
        confirmDialog(fI18n.t("Czy na pewno usunąć") + ` "${row.name}"?`).then(() => {
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
        const {languages, parents} = this.props;
        const page = this.state.page;

        return (
            <div>
                <CommandBar
                    isSearchBoxVisible={false}

                    items={[
                        {key: "f0", label: fI18n.t("Wróć"), icon: "Back", onClick: () => this.props._goto(this.props._baseURL + "/index")},
                        {key: "f1", label: fI18n.t("Zapisz") + ` [${this.state.language}]`, icon: "Save", onClick: () => this.handleSave()},
                        this.state.dirty ? {
                            key: "f3", label: fI18n.t("Anuluj") + ` `, icon: "Save", onClick: () => {
                                this.props._reloadProps();
                                this.setState({dirty: false});
                            },
                        } : null,

                    ]}/>
                <Navbar>
                    <span>Cms</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "/index")}>{fI18n.t("Strony www")}</a>
                    <span>{page.name}</span>
                </Navbar>

                <Row noGutters={false} md={[12]}>
                    <Tabs defaultActiveTab={this.state.indexTab} onTabChange={this.handleLangChange.bind(this)}>
                        {languages.map((el) => <TabPane title={el.name} key={el.id}>{null}</TabPane>)}
                        {/*<TabPane title={"Polski"} key={"pl"}>{null}</TabPane>*/}
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

                                        <BText label={fI18n.t("Link")} {...form("front_link")} />

                                    </Row>

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
                                </Panel>
                                }
                                {/*{page.type != "page" &&*/}
                                {/*<Panel title={fI18n.t("Pliki")}>*/}

                                    {/*<BFileListField label={fI18n.t("Nagłówek")} {...form("files[header]")} maxLength={1} type="gallery"/>*/}

                                    {/*<BFileListField label={fI18n.t("Galeria")} {...form("files[images]")} type={"gallery"}/>*/}

                                    {/*<BFileListField label={fI18n.t("Do pobrania")} {...form("files[files]")} />*/}

                                    {/*<BFileListField label={fI18n.t("Pliki przypisane")} {...form("files[assigned]")}  />*/}
                                {/*</Panel>*/}
                                {/*}*/}
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
        );
    }
}

export default EditFormHOC()(ArrowViewComponent);
