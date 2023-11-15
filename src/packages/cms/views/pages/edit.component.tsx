import * as React from "react";
import { Navbar } from "serenity-controls/lib/Navbar";
import {Column, Table} from "serenity-controls/lib/Table";
import {BFileListField, BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from "serenity-controls/lib/BForm";
import {Comm} from "serenity-controls/lib/lib";
import {Row} from "serenity-controls/lib/Row";

import {IArrowViewComponentProps} from "serenity-controls/lib/backoffice";
import {CommandBar} from "serenity-controls/lib/CommandBar";
import {Arrow} from "../../../../data/cache/db/ts-definitions";
import {Panel} from "serenity-controls/lib/Panel";
import {TabPane, Tabs} from "serenity-controls/lib/Tabs";
import IPage = Arrow.CMS.Models.Persistent.IPage;
import {confirmDialog} from "serenity-controls/lib/ConfirmDialog";
import { trans } from "../../../translations/front/trans";

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
        confirmDialog(trans("Czy na pewno usunąć") + ` "${row.name}"?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", {key: row.id}).then(() => {
                this.props._notification(trans("Pomyślnie usunięto") + ` "${row.name}"`);
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
            this.props._notification(toSend.page.name, trans("Zapisano pomyślnie"));
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
                        {key: "f0", label: trans("Wróć"), icon: "Back", onClick: () => this.props._goto(this.props._baseURL + "/index")},
                        {key: "f1", label: trans("Zapisz") + ` [${this.state.language}]`, icon: "Save", onClick: () => this.handleSave()},
                        this.state.dirty ? {
                            key: "f3", label: trans("Anuluj") + ` `, icon: "Save", onClick: () => {
                                this.props._reloadProps();
                                this.setState({dirty: false});
                            },
                        } : null,

                    ]}/>
                <Navbar>
                    <span>Cms</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "/index")}>{trans("Strony www")}</a>
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

                                <Panel noPadding={true} title={trans("Dane")}>

                                    <Row noGutters={false}>
                                        <BText label={trans("Nazwa")} {...form("name")} />
                                    </Row>
                                    {this.props.editEnabled ?
                                        <Row noGutters={false}>
                                            <BSwitch label={trans("Aktywna")} {...form("active")} options={{0: "Nie", 1: "Tak"}}/>
                                            <BSwitch label={trans("Typ")} {...form("type")} options={{page: trans("Strona"), container: trans("Folder")}}/>
                                        </Row>
                                        : null
                                    }
                                    <Row noGutters={false}>
                                        {/*<BSelect label="Język" {...form("language")} options={languages.map((el) => ({value: el.id, label: el.name}))}/>*/}
                                        {this.props.editEnabled ?
                                            <BSelect label={trans("Element nadrzędny")} {...form("parent_id")} options={parents.map((el) => ({value: el.id, label: el.name}))}/>
                                            : null
                                        }

                                        <BText label={trans("Link")} {...form("front_link")} />

                                    </Row>

                                </Panel>
                                {page.type == "page" &&
                                <Panel noPadding={true} title={trans("SEO")}>
                                    <Row noGutters={false}>
                                        <BText label={trans("SEO Title")} {...form("seo_title")} />
                                        <BText label={trans("SEO Keywords")} {...form("seo_keywords")} />
                                    </Row>
                                    <Row noGutters={false}>
                                        <BTextarea label={trans("SEO description")} {...form("seo_description")} />
                                    </Row>
                                    <Row noGutters={false}>
                                        <BTextarea label={trans("Dodatkowy tekst na stronie")} {...form("seo_page_text")} />
                                    </Row>
                                </Panel>
                                }
                                {/*{page.type != "page" &&*/}
                                {/*<Panel title={trans("Pliki")}>*/}

                                    {/*<BFileListField label={trans("Nagłówek")} {...form("files[header]")} maxLength={1} type="gallery"/>*/}

                                    {/*<BFileListField label={trans("Galeria")} {...form("files[images]")} type={"gallery"}/>*/}

                                    {/*<BFileListField label={trans("Do pobrania")} {...form("files[files]")} />*/}

                                    {/*<BFileListField label={trans("Pliki przypisane")} {...form("files[assigned]")}  />*/}
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
