import * as React from 'react';
import {BForm} from 'frontend/src/layout/BootstrapForm';
import Comm from 'frontend/src/lib/Comm';

declare var window: any;

export default class ArrowViewComponent extends React.Component<any, any> {
    form: BForm;

    constructor(props) {
        super(props);
        this.state = {
            form: {
                login: '',
                password: '',
                error: ''
            },
            loading: false
        };

    }

    handleSubmit() {

        let data = this.state.form;
        /* if (data.login == "" || data.password == "") {
             //this.props._notification("Wypełnij wszystkie pola", "Nie udało się zalogować", {level: "error"});
             return;
         }*/

        let comm = new Comm(window.reactBackOfficeVar.appBaseURL + '/access/loginAction');
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
                window.location.href = this.props.appPath + '/admin';
            }


        });
        //this.setState({loading: true});
        comm.send();

    }

    render() {
        let s = this.state;
        return <div className="login-view">

            <div className="login-background" style={{backgroundImage: `url( ${this.props.backgroundImage} )`}}>

                <div className="login-form-container">
                    <div className="title"> {this.props.applicationTitle}</div>
                    <BForm>
                        {() => <div>

                            <div>
                                <div className="input">
                                    <input type="text" autoFocus={true} value={s.form.login} onChange={(e) => this.setState({form: {...s.form, login: e.target.value}})} name={'login'} placeholder="Twój login"/>
                                </div>
                                <div className="input">
                                    <input type="password" name={'password'} value={s.form.password} onChange={(e) => this.setState({form: {...s.form, password: e.target.value}})} placeholder={'Twoje hasło'}/>
                                </div>

                            </div>

                            <div className="button">
                                <button
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
                        </div>}
                    </BForm>
                </div>
            </div>
        </div>;


    }

}
