import * as React from "react";

import { Navbar } from "frontend/lib/Navbar";
import { BForm, BText, BTextarea } from "frontend/lib/BForm";
import { Panel } from "frontend/lib/Panel";
import { Row } from "frontend/lib/Row";
import { Icon } from "frontend/lib/Icon";

export default class extends React.Component<any, any> {
    constructor(props) {
        super(props);
        this.state = {
            formData: { ...props.group },
            response: {},
        };
    }

    handleFormChange(e) {
        this.forceUpdate();
        let data = this.refs.form.getData();

        this.setState({ formData: data });
    }

    handleFormSuccess(e) {
        this.props._notification(`Zapisano ${e.form.getData().name}`);
    }

    render() {
        let data = this.state.formData || {};
        return (
            <div>
                <Navbar>
                    <span>System</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "/list")}>Grupy dostępu</a>
                    <span>{this.props.group ? this.props.group.name : "Dodaj"}</span>
                </Navbar>

                <div className="panel-body-margins">
                    <BForm
                        ref="form"
                        data={data}
                        namespace={"data"}
                        action={this.props._baseURL + "/save"}
                        onSuccess={this.handleFormSuccess.bind(this)}
                        onChange={this.handleFormChange.bind(this)}
                    >
                        {(form) => (
                            <Row md={[6]}>
                                <Panel
                                    title={"Formularz " + (this.props.group ? "edycji" : "dodania") + " grupy dostępu"}
                                >
                                    <BText label="Nazwa" {...form("name")} />
                                    <BTextarea label="Opis" {...form("description")} />

                                    <div className="hr-line-dashed" />
                                    <a
                                        onClick={() => this.props._goto(this.props._baseURL + "/list")}
                                        className="btn btn-default pull-right"
                                    >
                                        {" "}
                                        Anuluj
                                    </a>
                                    <button type="submit" className="btn btn-primary pull-right ">
                                        {" "}
                                        Zapisz
                                    </button>
                                    <div className="clearfix" />
                                </Panel>
                            </Row>
                        )}
                    </BForm>
                </div>
            </div>
        );
    }
}
