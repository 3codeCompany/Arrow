import React, {Component} from 'react';

import Navbar from 'frontend/src/ctrl/Navbar';
import {confirm} from 'frontend/src/ctrl/Overlays';
import {Column, Table} from 'frontend/src/ctrl/Table';
import {Row} from 'frontend/src/layout/BootstrapLayout';
import Comm from 'frontend/src/lib/Comm';
import {CommandBar} from "frontend/src/ctrl/CommandBar";

export default class access_access_users_list extends Component {
    constructor(props) {
        super(props);
        this.state = {};
    }

    handleDelete(row, event) {
        confirm(`Czy na pewno usunąć "${row.login}" ?`).then(() => {
            Comm._post(this.props.baseURL + '/delete', {key: row.id}).then(() => {
                this.props._notification(`Urzytkownik  "${row.login}" został usunięty.`);
                this.table.load();
            });
        });
    }

    render() {
        return (
          <div>
              <CommandBar
                isSearchBoxVisible={false}
                items={[
                    {
                        key: "f1", icon: "Add", label: "Dodaj", onClick: () => {
                            this.props._goto("access/users/edit");
                        }
                    }

                ]}

              />
              <Navbar>
                  <span>System</span>
                  <span>Użytkownicy</span>
              </Navbar>
              <Row>
                  <div className="panel-body-margins">
                      <Table
                        remoteURL={this.props.baseURL + "/getData"}
                        ref={(table) => this.table = table}
                        columns={[
                            Column.id("id", "Id"),
                            Column.bool("active", "Aktywny"),
                            Column.text("login", "Login"),
                            Column.email("email", "Email"),
                            Column.template("Grupy dostępu", (val, row) => {
                                if (row.groups.length > 0) {
                                    return <div><i className="fa fa-lock"></i> {row.groups.join(", ")}</div>;
                                } else {
                                    return <div className="lightgrey center"><i className="fa fa-times"></i></div>;
                                }
                            }),

                            Column.template("Zobacz", () => <div><i className="fa fa-search"></i></div>)
                              .onClick((row) => this.props._goto("access/users/edit", {key: row.id}))
                              .className("center darkgreen"),
                            Column.template("Zobacz", () => <i className="fa fa-times"></i>)
                              .onClick(this.handleDelete.bind(this))
                              .className("center darkred")
                        ]}
                      />
                  </div>
              </Row>
          </div>
        );
    }
}
