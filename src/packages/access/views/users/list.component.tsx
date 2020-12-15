import * as React from "react";

import { Navbar } from "serenity-controls/lib/Navbar";

import {Column, Table} from "serenity-controls/lib/Table";
import {Row} from "serenity-controls/lib/Row";
import {Comm} from "serenity-controls/lib/lib";
import {CommandBar} from "serenity-controls/lib/CommandBar";
import {Icon} from "serenity-controls/lib/Icon";
import {confirmDialog} from "serenity-controls/lib/ConfirmDialog";

export default class  extends React.Component<any, any> {
    public table: Table;

    constructor(props) {
        super(props);
        this.state = {};
    }

    public handleDelete(row, event) {
        confirmDialog(`Czy na pewno usunąć "${row.login}" ?`).then(() => {
            Comm._post(this.props._baseURL + "/delete", {key: row.id}).then(() => {
                this.props._notification(`Urzytkownik  "${row.login}" został usunięty.`);
                this.table.load();
            });
        });
    }

    public render() {
        return (
          <div>
              <CommandBar
                isSearchBoxVisible={false}
                items={[
                    {
                        key: "f1", icon: "Add", label: "Dodaj", onClick: () => {
                            this.props._goto("/access/users/edit");
                        },
                    },

                ]}

              />
              <Navbar>
                  <span>System</span>
                  <span>Użytkownicy</span>
              </Navbar>
              <Row>
                  <div className="panel-body-margins">
                      <Table
                        remoteURL={this.props._baseURL + "/getData"}
                        ref={(table) => this.table = table}
                        columns={[
                            Column.id("id", "Id"),
                            Column.bool("active", "Aktywny"),
                            Column.text("login", "Login"),
                            Column.email("email", "Email"),
                            Column.template("Grupy dostępu", (val, row) => {
                                if (row.groups.length > 0) {
                                    return <div><i className="fa fa-lock" /> {row.groups.join(", ")}</div>;
                                } else {
                                    return <div className="lightgrey center"><i className="fa fa-times" /></div>;
                                }
                            }),

                            Column.template("Zobacz", () => <div><Icon name={"Edit"} /> </div>)
                              .onClick((row) => this.props._goto("/access/users/edit", {key: row.id}))
                              .className("center darkgreen"),
                            Column.template("", () => <Icon name={"Delete"} /> )
                              .onClick(this.handleDelete.bind(this))
                              .className("center darkred"),
                        ]}
                      />
                  </div>
              </Row>
          </div>
        );
    }
}
