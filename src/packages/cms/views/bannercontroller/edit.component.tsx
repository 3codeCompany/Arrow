import * as React from "react";

import { Navbar } from "serenity-controls/lib/Navbar";
import {IArrowViewComponentProps} from "serenity-controls/lib/backoffice";
import {
    BForm,
    BText,
    BSwitch,
    BTextarea,
    BFileListField,
    BSelect,
    BDate
} from "serenity-controls/lib/BForm";
import {Panel} from "serenity-controls/lib/Panel";
import {Row} from "serenity-controls/lib/Row";
import {CommandBar} from "serenity-controls/lib/CommandBar";
import { trans } from "../../../translations/front/trans";

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
                    <span>{trans("Sklep")}</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "")}>{trans("Bannery")}</a>
                    <span>{trans("Edycja")} "{this.props.object.title}"</span>

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
                                                <BDate label={trans("Od")} {...form("start")}/>
                                                <BDate label={trans("Do")} {...form("stop")}/>
                                            </Row>

                                            <BSelect label={trans("Widoczność")} {...form("visibility")} options={{"all": "Uniwersalny", "male": "Mężczyzna", "female": "Kobieta"}}/>
                                            <BSelect label={trans("Kraj")} {...form("country")} options={this.props.countries}/>
                                            <BSelect label={trans("Język")} {...form("lang")} options={this.props.countries}/>
                                            <BSelect label={trans("Miejsce")} {...form("place")} options={this.props.places}/>

                                            <BText label={trans("Tytuł")} {...form("title")} />
                                            <BText label={trans("Podtytuł")} {...form("subtitle")}/>
                                            <BText label={trans("Link")} {...form("link")}/>
                                            <BTextarea label={trans("Opis")} {...form("description")}/>
                                        </Panel>

                                        <div>
                                            <Panel title={"Banner"} icon={"Upload"}>
                                                <BFileListField {...form("files[image]")} type={"gallery"}/>
                                            </Panel>
                                            <Panel title={"Video"} icon={"Upload"} noPadding={true}>
                                                <Row noGutters={false}>
                                                    <BFileListField buttonTitle={"Dodaj"} type={"gallery"}/>
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
