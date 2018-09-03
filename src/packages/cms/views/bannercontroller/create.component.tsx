import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {
    BForm,
    BSwitch,
    BText,
    BTextarea,
    BWysiwig,
    BFile,
    BSelect,
    BDate,
    BFileList
} from "frontend/src/layout/BootstrapForm";
import {Panel} from "frontend/src/ctrl/Panel";
import {Row} from "frontend/src/layout/BootstrapLayout";
import {CommandBar} from "frontend/src/ctrl/CommandBar";

interface IProps extends IArrowViewComponentProps {
    language: any;
    search: string;
    langToDownload: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    public form: any;

    constructor(props) {
        super(props);
        this.state = {};

    }

    public render() {
        const s = this.state;

        return (
            <div>
                <CommandBar items={[
                    {key: "f0", label: fI18n.t("Wróc"), icon: "Back", onClick: () => this.props._goto(this.props._baseURL + "")},
                    {key: "f1", label: fI18n.t("Zapisz"), icon: "Save", onClick: () => this.form.submit()},
                ]}/>

                <Navbar>
                    <span>{fI18n.t("Sklep")}</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "")}>{fI18n.t("Bannery")}</a>
                    <span>{fI18n.t("Dodaj banner")}</span>
                </Navbar>

                <div className={"panel-body-margins"}>
                    <BForm
                        onSuccess={() => {
                            this.props._notification("Ok", "Dodano nowy banner");
                        }}
                        ref={(el) => {
                            this.form = el;
                        }}
                        action={this.props._baseURL + "/store"}
                        namespace={"data"}
                    >
                        {(form) => {
                            return (
                                <div>
                                    <Row>
                                        <Panel title={"Dane podstawowe"} icon={"Edit"}>
                                            <Row>
                                                <BSwitch
                                                    options={[
                                                        {value: "1", label: fI18n.t("Tak")},
                                                        {value: "0", label: fI18n.t("nie")},
                                                    ]}
                                                    label={"Aktywna"} {...form("active")}
                                                />
                                                <BDate label={fI18n.t("Od")} {...form("start")}/>
                                                <BDate label={fI18n.t("Do")} {...form("stop")}/>
                                            </Row>

                                            <BSelect label={fI18n.t("Kraj")} {...form("country")}
                                                     options={this.props.countries}/>
                                            <BSelect label={fI18n.t("Język")} {...form("lang")}
                                                     options={this.props.countries}/>
                                            <BSelect label={fI18n.t("Miejsce")} {...form("place")}
                                                     options={this.props.places}/>

                                            <BText label={fI18n.t("Tytuł")} {...form("title")} />
                                            <BText label={fI18n.t("Podtytuł")} {...form("subtitle")}/>
                                            <BText label={fI18n.t("Link")} {...form("link")}/>
                                            <BTextarea label={fI18n.t("Opis")} {...form("description")}/>
                                        </Panel>

                                        <div>
                                            <Panel title={fI18n.t("Banner")} icon={"Upload"}>
                                                <BFileList {...form("files[image]")} type={"gallery"}/>
                                            </Panel>
                                            <Panel title={fI18n.t("Video")} icon={"Upload"} noPadding={true}>
                                                <Row noGutters={false}>
                                                    <BFileList buttonTitle={"Dodaj"} type={"gallery"}/>
                                                </Row>
                                            </Panel>
                                        </div>
                                    </Row>
                                </div>
                            );
                        }}
                    </BForm>
                </div>

            </div>
        );
    }
}
