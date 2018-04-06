import React, {Component} from "react";

import Navbar from "frontend/src/ctrl/Navbar"
import {BForm, BText, BSwitch, BCheckboxGroup,} from "frontend/src/layout/BootstrapForm"
import Panel from "frontend/src/ctrl/Panel"
import {Row} from "frontend/src/layout/BootstrapLayout"

export default class ArrowViewComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            formData: {...props.user, selectedGroups: this.props.selectedGroups, password: ""},
            response: {}
        };
    }

    handleFormChange(e) {
        this.forceUpdate();
        let data = this.refs.form.getData();

        this.setState({formData: data});
    }

    handleFormSuccess(e) {
        this.props._notification(`Zapisano ${e.form.getData().login}`);
    }


    render() {
        let data = this.state.formData || {};

        return (
            <div>
                <Navbar>
                    <span>Użytkownicy</span>
                    <span>{this.props.user.login}</span>
                </Navbar>


                <BForm
                    ref="form"
                    data={data}
                    namespace={"data"}
                    action={this.props.baseURL + "/saveAccount"}
                    onSuccess={this.handleFormSuccess.bind(this)}
                    onChange={this.handleFormChange.bind(this)}
                >
                    {(form) => <Row md={[6]}>
                        <Panel title={"Zmień swoje dane"}>
                            <BText label="Login" {...form("login")} editable={false} />


                            <BText label="Email" type="email" name="email"  {...form("email")}/>
                            <div className="hr-line-dashed"></div>
                            <BText
                                label="Stare hasło"
                                type="password"
                                {...form("password_old")}
                                name="password_old"
                                help={"Do zmiany hasła wymagane jest podanie starego hasła"}
                            />
                            <BText
                                label="Nowe hasło"
                                type="password"
                                {...form("password_new")}
                                name="password_new"
                                placeholder={ "Podaj hasło aby zmienić na nowe" }
                            />
                            <BText
                                label="Potwierdź nowe hasło"
                                type="password_confirm"
                                {...form("password_confirm")}
                                name="password"
                                placeholder={ "Podaj hasło aby zmienić na nowe" }
                            />

                            <div className="hr-line-dashed"></div>
                            <a onClick={() => this.props._goto(this.props.baseURL + "/list")} className="btn btn-default pull-right"> Anuluj</a>
                            <button type="submit" className="btn btn-primary pull-right "> Zapisz</button>
                            <div className="clearfix"></div>


                        </Panel>

                    </Row>}
                </BForm>


            </div>
        )
    }
}