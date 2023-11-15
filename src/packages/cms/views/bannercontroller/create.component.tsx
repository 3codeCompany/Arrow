import * as React from "react";

import { Navbar } from "serenity-controls/lib/Navbar";
import {IArrowViewComponentProps} from "serenity-controls/lib/backoffice";
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
        this.state = {};

    }

    public render() {
        const s = this.state;

        return (
            <div>
                <CommandBar items={[
                    {key: "f0", label: trans("Wróc"), icon: "Back", onClick: () => this.props._goto(this.props._baseURL + "")},
                    {key: "f1", label: trans("Zapisz"), icon: "Save", onClick: () => this.form.submit()},
                ]}/>

                <Navbar>
                    <span>{trans("Sklep")}</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "")}>{trans("Bannery")}</a>
                    <span>{trans("Dodaj banner")}</span>
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
                                                        {value: "1", label: trans("Tak")},
                                                        {value: "0", label: trans("nie")},
                                                    ]}
                                                    label={"Aktywna"} {...form("active")}
                                                />
                                                <BDate label={trans("Od")} {...form("start")}/>
                                                <BDate label={trans("Do")} {...form("stop")}/>
                                            </Row>

                                            <BSelect label={trans("Kraj")} {...form("country")}
                                                     options={this.props.countries}/>
                                            <BSelect label={trans("Język")} {...form("lang")}
                                                     options={this.props.countries}/>
                                            <BSelect label={trans("Miejsce")} {...form("place")}
                                                     options={this.props.places}/>

                                            <BText label={trans("Tytuł")} {...form("title")} />
                                            <BText label={trans("Podtytuł")} {...form("subtitle")}/>
                                            <BText label={trans("Link")} {...form("link")}/>
                                            <BTextarea label={trans("Opis")} {...form("description")}/>
                                        </Panel>

                                        <div>
                                            <Panel title={trans("Banner")} icon={"Upload"}>
                                                <BFileListField {...form("files[image]")} type={"gallery"}/>
                                            </Panel>
                                            <Panel title={trans("Video")} icon={"Upload"} noPadding={true}>
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
                </div>

            </div>
        );
    }
}
