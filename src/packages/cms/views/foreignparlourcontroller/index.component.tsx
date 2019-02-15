import * as React from "react";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {CommandBar} from "frontend/src/ctrl/CommandBar";
import Navbar from "frontend/src/ctrl/Navbar";
import {Column, Table} from "frontend/src/ctrl/Table";
import Icon from "frontend/src/ctrl/Icon";
import {confirm} from "frontend/src/ctrl/Overlays";
import Comm from "frontend/src/lib/Comm";

interface IProps extends IArrowViewComponentProps {
    groups: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    constructor(props) {
        super(props);
        this.state = {

        };

        this.table = null;
    }

    public render() {
        return (
            <div>
                {/*<CommandBar items={[*/}
                    {/*{key: "al", label: "Dodaj komplet", icon: "Add", onClick: () => {*/}
                            {/*this.props._goto(this.props._baseURL + "/create");*/}
                        {/*}},*/}
                {/*]}/>*/}
                <Navbar>
                    <span>{__("CMS")}</span>
                    <span>{__("Salony")}</span>
                </Navbar>

                <div className={"panel-body-margins"}>
                    <Table remoteURL={this.props._baseURL + "/asyncIndex"}
                           ref={(el) => this.table = el}
                           columns={[
                               Column.bool("active", "Aktywny"),
                               Column.text("city", "Miasto"),
                               Column.text("name", "Nazwa"),
                               Column.text("address", "Adres"),
                               Column.text("zip_code", "Kod pocztowy"),
                               Column.text("phone", "Telefon"),
                               Column.text("open", "Data otwarcia"),
                               Column.text("geo", "Geocode"),
                               Column.template("Edytuj", (val, row) => <Icon name={"Edit"}/> )
                                   .className("center")
                                   .width("60px")
                                   .onClick((row) => {
                                       this.props._goto(this.props._baseURL + `/${row.id}/edit`);
                                   }),
                           ]}
                    />
                </div>
            </div>
        );
    }
}
