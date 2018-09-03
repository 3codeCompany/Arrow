import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {
    BForm,
    BText,
    BSwitch,
    BTextarea,
    BFileList,
    BSelect,
    BDate
} from "frontend/src/layout/BootstrapForm";
import {Panel} from "frontend/src/ctrl/Panel";
import {Row} from "frontend/src/layout/BootstrapLayout";
import {CommandBar} from "frontend/src/ctrl/CommandBar";
import {fI18n} from "frontend/src/utils/I18n";

interface IProps extends IArrowViewComponentProps {
    language: any;
    search: string;
    langToDownload: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    public form: any;

    constructor(props) {
        super(props);
        this.state = {
            editPurpose: null,
        };
    }

    public render() {
        const s = this.state;
        return (
            <div>
                <CommandBar items={[
                    {key: "f0", label: "Wróc", icon: "Back", onClick: () => this.props._goto(this.props._baseURL + "")},
                    {key: "f1", label: "Zapisz", icon: "Save", onClick: () => {
                            this.setState({
                                editPurpose: "update",
                            });
                            setTimeout(() => {
                                this.form.submit();
                            }, 500)
                        }},
                    {
                        key: "b13", label: "Kopiuj banner", icon: "Copy", onClick: () =>
                        {
                            this.setState({
                                editPurpose: "copy",
                            });
                            setTimeout(() => {
                                this.form.submit();
                            }, 500)
                        }
                    },
                ]}/>

                <Navbar>
                    <span>{fI18n.t("Sklep")}</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "")}>{fI18n.t("Bannery")}</a>
                    <span>{fI18n.t("Edycja")} "{this.props.object.title}"</span>

                </Navbar>

                <div className={"panel-body-margins"}>
                    <Row md={[12]}>
                        <BForm
                            data={this.props.object}
                            ref={(el) => this.form = el}
                            action={this.props._baseURL + `/${this.props.object.id}/${this.state.editPurpose}`}
                            namespace={"data"}
                            onValidatorError={() => {
                                this.props._notification("Zmiana", "Wystąpił problem", {level: "error"});
                            }}
                            onSuccess={() => {
                                this.props._notification("Zmiana", "Dane zostały zmienione");
                                this.props._reloadProps();

                            }}

                        >{(form) => {
                            return (
                                <div>
                                    <Row>
                                        <Panel title={"Dane podstawowe"} icon={"Edit"}>
                                            <Row>
                                                <BSwitch
                                                    options={[
                                                        {value: "1", label: "Tak"},
                                                        {value: "0", label: "Nie"},
                                                    ]}
                                                    label={"Aktywna"} {...form("active")}
                                                />
                                                <BDate label={fI18n.t("Od")} {...form("start")}/>
                                                <BDate label={fI18n.t("Do")} {...form("stop")}/>
                                            </Row>

                                            <BSelect label={fI18n.t("Widoczność")} {...form("visibility")} options={{"all": "Uniwersalny", "male": "Mężczyzna", "female": "Kobieta"}}/>
                                            <BSelect label={fI18n.t("Kraj")} {...form("country")} options={this.props.countries}/>
                                            <BSelect label={fI18n.t("Język")} {...form("lang")} options={this.props.countries}/>
                                            <BSelect label={fI18n.t("Miejsce")} {...form("place")} options={this.props.places}/>

                                            <BText label={fI18n.t("Tytuł")} {...form("title")} />
                                            <BText label={fI18n.t("Podtytuł")} {...form("subtitle")}/>
                                            <BText label={fI18n.t("Link")} {...form("link")}/>
                                            <BTextarea label={fI18n.t("Opis")} {...form("description")}/>
                                        </Panel>

                                        <div>
                                            <Panel title={"Banner"} icon={"Upload"}>
                                                <BFileList {...form("files[image]")} type={"gallery"}/>
                                            </Panel>
                                            <Panel title={"Video"} icon={"Upload"} noPadding={true}>
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
                    </Row>
                </div>
            </div>
        );
    }
}
