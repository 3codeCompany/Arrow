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
        let groups = [];
        for (let i in this.props.groups) {

            groups.push({value: i, label: this.props.groups[i]})
        }

        return (
            <div>
                <Navbar>
                    <span>System</span>
<<<<<<< HEAD
                    <a onClick={() => this.props._goto(this.props.baseURL + "/list")}>Użytkownicy</a>
=======
                    <a onClick={() => this.props._goto( "access/users/list")}>Użytkownicy</a>
>>>>>>> 48b53524a967b453047c1ed0b071d6c459a0526b
                    <span>{this.props.user ? this.props.user.login : "Dodaj"}</span>
                </Navbar>


                <BForm
                    ref="form"
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
                            <div className="hr-line-dashed"></div>
                            <BText label="Hasło" type="password"  {...form("password")} name="password" placeholder={data.id ? "Podaj hasło aby zmienić na nowe" : ""}/>

                            <div className="hr-line-dashed"></div>
<<<<<<< HEAD
                            <a onClick={() => this.props._goto(this.props.baseURL + "/list")} className="btn btn-default pull-right"> Anuluj</a>
=======
                            <a onClick={() => this.props._goto( "access/users/list")} className="btn btn-default pull-right"> Anuluj</a>
>>>>>>> 48b53524a967b453047c1ed0b071d6c459a0526b
                            <button type="submit" className="btn btn-primary pull-right "> Zapisz</button>
                            <div className="clearfix"></div>


                        </Panel>
                        <Panel>
                            <BCheckboxGroup label="Grupy dostępu" name="selectedGroups"  {...form("selectedGroups")} inline={false} options={groups || []}/>
                        </Panel>
                    </Row>}
                </BForm>


            </div>
        )
    }
}
