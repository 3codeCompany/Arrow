import * as React from "react";
import {BForm} from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
declare var LANGUAGE: string;
declare var window: any;

interface IViewProps extends IArrowViewComponentProps {
    redirectTo: string;
    backgroundImage: string;
    applicationTitle: string;
}

export default class ArrowViewComponent extends React.Component<IViewProps, any> {
    public form: BForm;

    constructor(props) {
        super(props);
        this.state = {
            form: {
                login: "",
                password: "",
                error: "",
            },
            loading: false,
        };

    }

    public handleLangChange = (lang) => {
        Comm._get( "/admin/changeLang/" + lang).then(() => {
            window.location.reload();
        });
    }

    public handleSubmit = () => {
        const data = this.state.form;
        if (data.login == "" || data.password == "") {
            this.props._notification( fI18n.t("Wypełnij wszystkie pola"), fI18n.t("Nie udało się zalogować"), {level: "error"});
            return;
        }

        this.props._startLoadingIndicator();

        const comm = new Comm(this.props._baseURL + "/loginAction");
        comm.setData({data});

        this.setState({loading: true, error: ""});
        comm.on(Comm.EVENTS.ERROR, (response) => {
            this.setState({loading: false});
        });
        comm.on(Comm.EVENTS.VALIDATION_ERRORS, (response) => {
            this.setState({loading: false});
            this.setState({error: fI18n.t("Nieprawidłowy użytkownik lub hasło")});
        });
        comm.on(Comm.EVENTS.SUCCESS, (response) => {
            this.setState({loading: false, error: ""});
            /*this.props._goto(
                this.props._basePath + "/admin/dashboard",
                {},
                () => {
                    alert("here 111");
                    this.props._setPanelOption("onlyBody", false);
                },
            );*/
            if (this.props.redirectTo && false) {
                //window.location.href = this.props.redirectTo;
            } else {
                window.location.href = this.props._basePath + "/admin/dashboard";
            }

        });
        comm.on(Comm.EVENTS.FINISH, () => {
            this.props._stopLoadingIndicator();
        });
        comm.send();

    }

    public render() {
        const s = this.state;

        return <div className="login-view">
            <div className="lang-select">
                {window.reactBackOfficeVar.panel.languages.map((el) =>
                    <a key={el} className={el.toLowerCase() == LANGUAGE ? "active" : ""} onClick={() => this.handleLangChange(el)}>{el.toUpperCase()}</a>,
                )}
            </div>

            <div className="login-background" style={{backgroundImage: `url( ${this.props.backgroundImage} )`}}>

                <div className="login-form-container">
                    <div className="title"> {this.props.applicationTitle}</div>
                    <BForm>
                        {() => <div>

                            <div>
                                <div className="input">
                                    <input type="text" autoFocus={true} value={s.form.login} onChange={(e) => this.setState({form: {...s.form, login: e.target.value}})} name={"login"} placeholder={fI18n.t("Podaj swój login")}/>
                                </div>
                                <div className="input">
                                    <input type="password" name={"password"} value={s.form.password} onChange={(e) => this.setState({form: {...s.form, password: e.target.value}})} placeholder={fI18n.t("Podaj hasło")}/>
                                </div>

                            </div>

                            <div className="button">
                                <button
                                    className="login-button"
                                    disabled={this.state.loading}
                                    onClick={this.handleSubmit}
                                >
                                    {this.state.loading ? "..." : fI18n.t("zaloguj się")}
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
