import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {
    BForm,
    BText,
    BWysiwig,
    BSwitch,
    BTextarea,
    BFile,
    BFileList,
    BSelect,
    BDate
} from "frontend/src/layout/BootstrapForm";
import {Panel} from "frontend/src/ctrl/Panel";
import {Row} from "frontend/src/layout/BootstrapLayout";
import {CommandBar} from "frontend/src/ctrl/CommandBar";
import {Tabs, TabPane} from "frontend/src/ctrl/Tabs";
import {Column, Table} from "frontend/src/ctrl/Table";
import {confirm, Modal} from "frontend/src/ctrl/Overlays";
import Icon from "frontend/src/ctrl/Icon";
import Comm from "frontend/src/lib/Comm";
import {Searcher} from "../../../../../../../node_modules_shared/frontend/src/ctrl/Searcher";

const Selectivity = require("selectivity/react");

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
                                                <BDate label={__("Od")} {...form("start")}/>
                                                <BDate label={__("Do")} {...form("stop")}/>
                                            </Row>

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
