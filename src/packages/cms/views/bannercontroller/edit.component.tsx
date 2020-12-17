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

    public dateTimeValidate = (value) => {
        const regexDate = /^\d{4}-\d{1,2}-\d{1,2}$/;
        const regexTime = /^\d{1,2}:\d{1,2}:\d{2}([ap]m)?$/;
        const date = value.split(" ")[0];
        const time = value.split(" ")[1];

        if(date != '' && !date.match(regexDate)) {
            alert("Niepoprawny format daty: " + value);
            return false;
        }

        if(time != '' && !time.match(regexTime)) {
            alert("Niepoprawny format czasu: " + value);
            return false;
        }

        return true;
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
                                const formValues = this.form.fieldsValues;
                                let validateStatus;
                                validateStatus = this.dateTimeValidate(formValues.start);
                                if (validateStatus) {
                                    validateStatus = this.dateTimeValidate(formValues.stop);
                                }

                                if (validateStatus) {
                                    this.form.submit();
                                }

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
                    <span>{__("Sklep")}</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "")}>{__("Bannery")}</a>
                    <span>{__("Edycja")} "{this.props.object.title}"</span>

                </Navbar>

                <div className={"panel-body-margins"}>
                    <Row md={[12]}>
                        <BForm
                            data={this.props.object}
                            ref={(el) => this.form = el}
                            action={this.props._baseURL + `/${this.props.object.id}/${this.state.editPurpose}`}
                            namespace={"data"}
                            onValidatorError={(error) => {
                                this.props._notification("Zmiana ustawień", "Wystąpił problem", {level: "error"});
                            }}
                            /*onValidatorError={(error) => this.props._notification(error.response.errors.join(" | "), "Błąd", {level: "error"})}*/
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
                                                <div className={"col-md-4"} style={{paddingLeft: 0}}>
                                                <BSwitch
                                                    options={[
                                                        {value: "1", label: "Tak"},
                                                        {value: "0", label: "Nie"},
                                                    ]}
                                                    label={"Aktywna"} {...form("active")}
                                                />
                                                </div>
                                                <div className={"col-md-4"}>
                                                    <BText label={__("Data od")} {...form("start")}/>
                                                </div>
                                                <div className={"col-md-4"}>
                                                    <BText label={__("Data do")} {...form("stop")}/>
                                                </div>
                                            </Row>
                                            <BText label={__("Czas wyświetlania") + " [ms]    (5000ms = 5s)"} {...form("duration")} />
                                            <BSelect label={__("Widoczność")} {...form("visibility")} options={{"all": "Uniwersalny", "male": "Mężczyzna", "female": "Kobieta"}}/>
                                            <BSelect label={__("Kraj")} {...form("country")} options={this.props.countries}/>
                                            <BSelect label={__("Język")} {...form("lang")} options={this.props.countries}/>
                                            <BSelect label={__("Miejsce")} {...form("place")} options={this.props.places}/>

                                            <BText label={__("Tytuł")} {...form("title")} />
                                            <BText label={__("Podtytuł")} {...form("subtitle")}/>
                                            <BText label={__("Link")} {...form("link")}/>
                                            <BTextarea label={__("Opis")} {...form("description")}/>
                                        </Panel>

                                        <div>
                                            <Panel title={"Banner"} icon={"Upload"}>
                                                <BFileList {...form("files[image]")} type={"gallery"}/>
                                            </Panel>

                                            <Panel title={"Video"} icon={"Upload"}>
                                                <BFileList {...form("files[video]")} type={"gallery"}/>
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
