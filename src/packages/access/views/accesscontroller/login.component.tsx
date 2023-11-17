import * as React from "react";
import { BForm } from "serenity-controls/lib/BForm";
import { Comm } from "serenity-controls/lib/lib";
import { IArrowViewComponentProps } from "serenity-controls/lib/backoffice";
import { fI18n } from "serenity-controls/lib/lib/I18n";
import { configGetAll } from "serenity-controls/lib/backoffice/Config";

declare var LANGUAGE: string;
declare var window: any;

interface IViewProps extends IArrowViewComponentProps {
    redirectTo: string;
    backgroundImage: string;
    applicationTitle: string;
}

export default class ArrowViewComponent extends React.Component<IViewProps, any> {
    public form: BForm;

    constructor(props: IViewProps) {
        super(props);
        this.state = {
            form: {
                login: "",
                password: "",
            },
            error: false,
            loading: false,
        };
    }

    public handleLangChange = (lang) => {
        Comm._get("/admin/changeLang/" + lang).then(() => {
            window.location.reload();
        });
    };

    public componentDidMount() {
        document.getElementsByClassName("w-panel-body-container")[0].style.height = "100%";
    }

    public handleSubmit = () => {
        const data = this.state.form;
        if (data.login == "" || data.password == "") {
            this.props._notification(fI18n.t("Wypełnij wszystkie pola"), fI18n.t("Nie udało się zalogować"), {
                level: "error",
            });
            return;
        }

        this.props._startLoadingIndicator();

        const comm = new Comm("/auth/login");
        comm.setData({ data });

        this.setState({ loading: true, error: false });
        comm.on(Comm.EVENTS.ERROR, (response) => {
            this.setState({ loading: false });
        });
        comm.on(Comm.EVENTS.FINISH, (response) => {
            console.log(response, "finish");
        });
        comm.on(Comm.EVENTS.VALIDATION_ERRORS, (response) => {
            console.log(response);
            this.setState({ loading: false, error: response });
        });
        comm.on(Comm.EVENTS.SUCCESS, (response) => {
            this.setState({ loading: false, error: false });

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
    };

    public render() {
        const s = this.state;
        const currLang = configGetAll().translations.currentLanguage;

        return (
            <div className="login-view" style={{ backgroundImage: `url( https://crm.as-pl.com/next/login_background.webp )`, height: "100vh", width: "100vw" }}>
                <img
                    width="186"
                    height="75"
                    style={{ position: "absolute", left: 20, top: 20 }}
                    src="https://erp-dev.localhost/next/_next/image?url=%2Fnext%2Flogo.png&w=256&q=75"
                />
                <div className="lang-select">
                    {window.reactBackOfficeVar.panel.languages.map((el) => (
                        <a key={el} className={el.toLowerCase() == currLang ? "active" : ""} onClick={() => this.handleLangChange(el)}>
                            {el.toUpperCase()}
                        </a>
                    ))}
                </div>

                <div className="login-background" style={{ boxShadow: "none" }}>
                    <div className="login-form-container">
                        <div className="title"> {this.props.applicationTitle}</div>
                        <BForm>
                            {() => (
                                <div>
                                    <div>
                                        <div className="input">
                                            <input
                                                type="text"
                                                autoFocus={true}
                                                value={s.form.login}
                                                onChange={(e) => this.setState({ form: { ...s.form, login: e.target.value } })}
                                                name={"login"}
                                                placeholder={fI18n.t("Podaj swój login")}
                                            />
                                        </div>
                                        <div className="input">
                                            <input
                                                type="password"
                                                name={"password"}
                                                value={s.form.password}
                                                onChange={(e) => this.setState({ form: { ...s.form, password: e.target.value } })}
                                                placeholder={fI18n.t("Podaj hasło")}
                                            />
                                        </div>
                                    </div>

                                    <div className="button">
                                        <button className="login-button" disabled={this.state.loading} onClick={this.handleSubmit}>
                                            {this.state.loading ? "..." : fI18n.t("zaloguj się")}
                                        </button>
                                    </div>
                                    <div style={{ height: 20, paddingTop: 10 }}>{this.state.error ? this.state.error.formErrors.join("") : ""}</div>
                                </div>
                            )}
                        </BForm>
                    </div>
                </div>
            </div>
        );
    }
}
