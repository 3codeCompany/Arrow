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
                    {key: "f0", label: __("Wróc"), icon: "Back", onClick: () => this.props._goto(this.props._baseURL + "")},
                    {key: "f1", label: __("Zapisz"), icon: "Save", onClick: () => this.form.submit()},
                ]}/>

                <Navbar>
                    <span>{__("Sklep")}</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "")}>{__("Bannery")}</a>
                    <span>{__("Dodaj banner")}</span>
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
                                                        {value: "1", label: __("Tak")},
                                                        {value: "0", label: __("nie")},
                                                    ]}
                                                    label={"Aktywna"} {...form("active")}
                                                />
                                                <BDate label={__("Od")} {...form("start")}/>
                                                <BDate label={__("Do")} {...form("stop")}/>
                                            </Row>

                                            <BSelect label={__("Kraj")} {...form("country")}
                                                     options={this.props.countries}/>
                                            <BSelect label={__("Język")} {...form("lang")}
                                                     options={this.props.countries}/>
                                            <BSelect label={__("Miejsce")} {...form("place")}
                                                     options={this.props.places}/>

                                            <BText label={__("Tytuł")} {...form("title")} />
                                            <BText label={__("Podtytuł")} {...form("subtitle")}/>
                                            <BText label={__("Link")} {...form("link")}/>
                                            <BTextarea label={__("Opis")} {...form("description")}/>
                                        </Panel>

                                        <div>
                                            <Panel title={__("Banner")} icon={"Upload"}>
                                                <BFileList {...form("files[image]")} type={"gallery"}/>
                                            </Panel>
                                            <Panel title={__("Video")} icon={"Upload"} noPadding={true}>
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
