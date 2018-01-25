import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import {BForm, BText, BSwitch, BCheckboxGroup} from "frontend/src/layout/BootstrapForm";
import Panel from "frontend/src/ctrl/Panel";
import {Row} from "frontend/src/layout/BootstrapLayout";
import {Icon} from "frontend/src/ctrl/Icon";

export default class  extends React.Component<any, any> {
    public form: BForm;

    constructor(props) {
        super(props);
        this.state = {
            formData: {...props.user, selectedGroups: this.props.selectedGroups, password: ""},
            response: {},
        };
    }

    public handleFormChange(e) {
        this.forceUpdate();
        const data = this.form.getData();

        this.setState({formData: data});
    }

    public handleFormSuccess(e) {
        this.props._notification(`Zapisano ${e.form.getData().login}`);
    }

    public render() {
        const data = this.state.formData || {};
        const groups = [];
        for (const i in this.props.groups) {

            groups.push({value: i, label: this.props.groups[i]});
        }

        return (
            <div>
                <Navbar>
                    <span>System</span>
                    <a onClick={() => this.props._goto( "/access/users/list")}>Użytkownicy</a>
                    <span>{this.props.user ? this.props.user.login : "Dodaj"}</span>
                </Navbar>

                <BForm
                    ref={(el) => this.form = el}
                    data={data}
                    namespace={"data"}
                    action={this.props.baseURL + "/save"}
                    onSuccess={this.handleFormSuccess.bind(this)}
                    onChange={this.handleFormChange.bind(this)}
                >
                    {(form) => <Row>
                        <Panel title={"Formularz " + (this.props.user ? "edycji" : "dodania") + " użytkownika"}>
                            <BText label="Login" {...form("login")} />
                            <BSwitch label="Konto aktywne" inline={true} options={{0: "Nie", 1: "Tak"}}  {...form("active")} />

                            <BText label="Email" type="email" name="email"  {...form("email")}/>
                            <div className="hr-line-dashed" />
                            <BText label="Hasło" type="password"  {...form("password")} name="password" placeholder={data.id ? "Podaj hasło aby zmienić na nowe" : ""}/>

                            <div className="hr-line-dashed" />
                            <a onClick={() => this.props._goto( "/access/users/list")} className="btn btn-default pull-right"> Anuluj</a>
                            <button type="submit" className="btn btn-primary pull-right "> Zapisz</button>
                            <div className="clearfix" />

                        </Panel>
                        <Panel>
                            <BCheckboxGroup label="Grupy dostępu" name="selectedGroups"  {...form("selectedGroups")} inline={false} options={groups || []}/>
                        </Panel>
                    </Row>}
                </BForm>

            </div>
        );
    }
}
