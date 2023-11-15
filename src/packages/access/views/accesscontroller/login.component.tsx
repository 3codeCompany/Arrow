import * as React from "react";
import { BForm } from "serenity-controls/lib/BForm";
import { Comm } from "serenity-controls/lib/lib";
import { IArrowViewComponentProps } from "serenity-controls/lib/backoffice";
import { PrintJSON } from "serenity-controls/lib/PrintJSON";
import { configGetAll } from "serenity-controls/lib/backoffice/Config";
import { trans } from "../../../translations/front/trans";

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

    public handleSubmit = () => {
        const data = this.state.form;
        if (data.login == "" || data.password == "") {
            this.props._notification(trans("Wypełnij wszystkie pola"), trans("Nie udało się zalogować"), {
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
            <div className="login-view">
                <div className="lang-select">
                    {window.reactBackOfficeVar.panel.languages.map((el) => (
                        <a
                            key={el}
                            className={el.toLowerCase() == currLang ? "active" : ""}
                            onClick={() => this.handleLangChange(el)}
                        >
                            {el.toUpperCase()}
                        </a>
                    ))}
                </div>

                <div className="login-background" style={{ backgroundImage: `url( ${this.props.backgroundImage} )` }}>
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
                                                onChange={(e) =>
                                                    this.setState({ form: { ...s.form, login: e.target.value } })
                                                }
                                                name={"login"}
                                                placeholder={trans("Podaj swój login")}
                                            />
                                        </div>
                                        <div className="input">
                                            <input
                                                type="password"
                                                name={"password"}
                                                value={s.form.password}
                                                onChange={(e) =>
                                                    this.setState({ form: { ...s.form, password: e.target.value } })
                                                }
                                                placeholder={trans("Podaj hasło")}
                                            />
                                        </div>
                                    </div>

                                    <div className="button">
                                        <button
                                            className="login-button"
                                            disabled={this.state.loading}
                                            onClick={this.handleSubmit}
                                        >
                                            {this.state.loading ? "..." : trans("zaloguj się")}
                                        </button>
                                    </div>
                                    <div style={{ height: 20, paddingTop: 10 }}>
                                        {this.state.error ? this.state.error.formErrors.join("") : ""}
                                    </div>
                                </div>
                            )}
                        </BForm>
                    </div>
                </div>
            </div>
        );
    }
}
