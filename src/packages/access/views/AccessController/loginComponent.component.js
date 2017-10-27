import React, {Component} from "react";
import Panel from "frontend/src/ctrl/Panel";
import {BForm, BText} from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";


export default class ArrowViewComponent extends Component {

    constructor(props) {
        super(props);
        this.state = {
            form: {
                login: "",
                password: ""
            },
            loading: false
        }

    }

    handleSubmit() {
        let data = this.state.form;
        if (data.login == "" || data.password == "") {
            //this.props._notification("Wypełnij wszystkie pola", "Nie udało się zalogować", {level: "error"});
            return;
        }

        let comm = new Comm("/access/auth/loginAction");
        comm.setData({data: data});


        comm.on("success", (response) => {
            this.setState({loading: false});
            if (!response[0]) {
                //this.props._notification("Nieprawidłowy użytkownik lub hasło", "Nie udało się zalogować", {level: "error"});
            } else {
                if (this.props.from) {
                    window.location.href = this.props.from;
                } else {
                    window.location.href = this.props.appPath + "admin";
                }

            }
        })
        this.setState({loading: true});
        comm.send();
    }

    render() {
        let s = this.state;
        return <div className="login-view">

            <div className="login-background">

                <div className="login-form-container">
                    <div className="title"> {this.props.applicationTitle}</div>
                    <BForm>
                        {() => <div>
                            <div style={{width: "calc( 100% - 20px )"}}>
                                <div className="input">
                                    <input type="text" autoFocus={true} value={s.form.login} onChange={(e) => this.setState({form: {...s.form, login: e.target.value}})} name={"login"} placeholder="Twój login"/>
                                </div>
                                <div className="input">
                                    <input type="password" name={"password"} value={s.form.password} onChange={(e) => this.setState({form: {...s.form, password: e.target.value}})} placeholder={"Twoje hasło"}/>
                                </div>
                            </div>

                            <div className="button">
                                <button
                                    className="login-button"
                                    disabled={this.state.loading}
                                    onClick={this.handleSubmit.bind(this)}
                                >
                                    {this.state.loading ? <i className="fa fa-spinner fa-spin"/> : null} Zaloguj się
                                </button>
                            </div>
                        </div>}
                    </BForm>
                </div>
            </div>
        </div>;


    }

}
