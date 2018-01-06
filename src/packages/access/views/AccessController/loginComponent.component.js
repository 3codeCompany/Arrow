<<<<<<< HEAD
import React, {Component} from "react";
import Panel from "frontend/src/ctrl/Panel";
import {BForm, BText} from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";
=======
import React, {Component} from 'react';
import {BForm} from 'frontend/src/layout/BootstrapForm';
import Comm from 'frontend/src/lib/Comm';
>>>>>>> 48b53524a967b453047c1ed0b071d6c459a0526b


export default class ArrowViewComponent extends Component {

    constructor(props) {
        super(props);
        this.state = {
            form: {
<<<<<<< HEAD
                login: "",
                password: ""
            },
            loading: false
        }
=======
                login: '',
                password: '',
                error: ''
            },
            loading: false
        };
>>>>>>> 48b53524a967b453047c1ed0b071d6c459a0526b

    }

    handleSubmit() {
<<<<<<< HEAD
        let data = this.state.form;
        if (data.login == "" || data.password == "") {
            this.props._notification("Wypełnij wszystkie pola", "Nie udało się zalogować", {level: "error"});
            return;
        }

        let comm = new Comm("/access/auth/loginAction");
        comm.setData({data: data});


        comm.on("success", (response) => {
            this.setState({loading: false});
            if (!response[0]) {
                this.props._notification("Nieprawidłowy użytkownik lub hasło", "Nie udało się zalogować", {level: "error"});
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
=======

        let data = this.state.form;
        /* if (data.login == "" || data.password == "") {
             //this.props._notification("Wypełnij wszystkie pola", "Nie udało się zalogować", {level: "error"});
             return;
         }*/

        let comm = new Comm(window.reactBackOfficeVar.appBaseURL + 'access/accessController/loginAction');
        comm.setData({data: data});


        this.setState({loading: true, error: ''});
        comm.on(Comm.EVENTS.VALIDATION_ERRORS, (response) => {
            this.setState({loading: false});
            this.setState({error: 'Nieprawidłowy użytkownik lub hasło'});
        });
        comm.on(Comm.EVENTS.SUCCESS, (response) => {
            this.setState({loading: false, error: ''});
            if (this.props.redirectTo) {
                window.location.href = this.props.redirectTo;
            } else {
                window.location.href = this.props.appPath + 'admin';
            }


        });
        //this.setState({loading: true});
        comm.send();

>>>>>>> 48b53524a967b453047c1ed0b071d6c459a0526b
    }

    render() {
        let s = this.state;
        return <div className="login-view">

            <div className="login-background">

                <div className="login-form-container">
                    <div className="title"> {this.props.applicationTitle}</div>
                    <BForm>
                        {() => <div>
<<<<<<< HEAD
                            <div style={{width: "calc( 100% - 20px )"}}>
                                <div className="input">
                                    <input type="text" autoFocus={true} value={s.form.login} onChange={(e) => this.setState({form: {...s.form, login: e.target.value}})} name={"login"} placeholder="Twój login"/>
                                </div>
                                <div className="input">
                                    <input type="password" name={"password"} value={s.form.password} onChange={(e) => this.setState({form: {...s.form, password: e.target.value}})} placeholder={"Twoje hasło"}/>
                                </div>
=======
                            <div>
                                <div className="input">
                                    <input type="text" autoFocus={true} value={s.form.login} onChange={(e) => this.setState({form: {...s.form, login: e.target.value}})} name={'login'} placeholder="Twój login"/>
                                </div>
                                <div className="input">
                                    <input type="password" name={'password'} value={s.form.password} onChange={(e) => this.setState({form: {...s.form, password: e.target.value}})} placeholder={'Twoje hasło'}/>
                                </div>

>>>>>>> 48b53524a967b453047c1ed0b071d6c459a0526b
                            </div>

                            <div className="button">
                                <button
<<<<<<< HEAD
                                    className="login-button"
                                    disabled={this.state.loading}
                                    onClick={this.handleSubmit.bind(this)}
                                >
                                    {this.state.loading ? <i className="fa fa-spinner fa-spin"/> : null} Zaloguj się
                                </button>
                            </div>
=======
                                  className="login-button"
                                  disabled={this.state.loading}
                                  onClick={this.handleSubmit.bind(this)}
                                >
                                    {this.state.loading ? '...' : 'Zaloguj się'}
                                </button>
                            </div>
                            <div style={{height: 20, paddingTop: 10}}>
                                {this.state.error}
                            </div>
>>>>>>> 48b53524a967b453047c1ed0b071d6c459a0526b
                        </div>}
                    </BForm>
                </div>
            </div>
        </div>;


    }

}
