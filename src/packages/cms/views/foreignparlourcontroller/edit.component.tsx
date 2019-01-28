import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {BForm, BText, BSelect, BSwitch, BConnectionsField, BFile, BFileList} from "frontend/src/layout/BootstrapForm";
import {Panel} from "frontend/src/ctrl/Panel";
import {Row} from "frontend/src/layout/BootstrapLayout";
import {CommandBar} from "frontend/src/ctrl/CommandBar";
import {Tabs, TabPane} from "frontend/src/ctrl/Tabs";
import {Column, Table} from "frontend/src/ctrl/Table";
import {confirm} from "frontend/src/ctrl/Overlays";
import Icon from "frontend/src/ctrl/Icon";
import Comm from "frontend/src/lib/Comm";
import {Searcher} from "../../../../../../../node_modules_shared/frontend/src/ctrl/Searcher";

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
        };

    }

    public render() {

        return (
            <div>
                <CommandBar items={[
                    {key: "f0", label: "Wróc", icon: "Back", onClick: () => this.props._goto(this.props._baseURL + "")},
                    {key: "f1", label: "Zapisz", icon: "Save", onClick: () => this.form.submit()}
                ]}/>

                <Navbar>
                    <span>{__("CMS")}</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "")}>{__("Salony")}</a>
                    <span>{__("Edycja")} "{this.props.object.name}"</span>

                </Navbar>

                <div className={"panel-body-margins"}>

                    <Row md={[12]}>
                        <BForm
                            data={this.props.object}
                            ref={(el) => this.form = el}
                            action={this.props._baseURL + `/${this.props.object.id}/update`}
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
                                    <Panel title={"Dane podstawowe"} icon={"Edit"}>

                                        <BText label={"Nazwa"} {...form("name")} />

                                        <BSwitch
                                            options={[
                                                {value: "1", label: "Polski"},
                                                {value: "2", label: "Niemiecki"},
                                            ]}
                                            label={"Aktywny"} {...form("active")}
                                        />
                                        <BText label={"Miasto"} {...form("city")} />
                                        <BText label={"Adres"} {...form("address")} />
                                        <BText label={"Kod pocztowy"} {...form("zip_code")} />
                                        <BText label={"Telefon"} {...form("phone")} />
                                        <BText label={"Geocode"} {...form("geo")} />

                                    </Panel>
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
