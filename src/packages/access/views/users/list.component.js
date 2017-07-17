import React, {Component} from 'react';

import Navbar from 'frontend/src/ctrl/Navbar'

import {BForm, BText, BSwitch, BSelect, BCheckboxGroup, BTextarea} from 'frontend/src/layout/BootstrapForm'
import {Table, Column, Filter} from 'frontend/src/ctrl/Table'
import Panel from 'frontend/src/ctrl/Panel'

import {SimpleTable, SimpleTableRow} from 'frontend/src/ctrl/SimpleTable'
import {Row} from 'frontend/src/layout/BootstrapLayout'
import {Timeline, TimelineItem} from 'frontend/src/ctrl/Timeline'
import {Tabs, TabPane} from 'frontend/src/ctrl/Tabs'
import {Comments, CommentItem} from 'frontend/src/ctrl/Comments'
import {Modal} from 'frontend/src/ctrl/Overlays'
export default class access_access_users_list extends Component {
    constructor(props) {
        super(props);
        this.state = {};
    }

    handleDelete(row, event) {

        alertify.confirm("Potwierdzenie", `Czy na pewno usunąć "${row.login}" ?`,
            () => {
                $.get(this.props.baseURL + "/delete?key=" + row.id, () => this.refs.table.load())
            },
            () => {
            }
        )
    }

    render() {
        return (
            <div>
                <Navbar>
                    <span>System</span>
                    <span>Użytkownicy</span>
                </Navbar>
                <Row>
                    <Panel title="Lista użytkowników systemu">
                        <a href={'#' + this.props.baseURL + "/edit"} className="btn btn-sm btn-primary"><i className="fa fa-plus"></i>Dodaj</a>
                        <Table remoteURL={this.props.baseURL + '/getData'} ref="table">
                            <Column field="id" caption="Id" className="right">
                                <Filter type="numeric"/>
                            </Column>
                            <Column field="active"
                                    caption="Aktywny"
                                    className="center"
                                /*type="Map"
                                 map={[ "Nie", "Tak" ]}*/
                                    template={(value, row) =>
                                        <i className={'fa fa-' + (value=="1" ? 'check darkgreen' : 'times darkred')}></i>
                                    }
                            >
                                <Filter type="select" content={{0: "Nie", 1: "Tak"}} multiselect="1"/>
                            </Column>
                            <Column field="login" caption="Login" icon="fa-user">
                                <Filter type="text"/>
                            </Column>
                            <Column field="email" caption="Email">
                                <Filter type="text"/>
                            </Column>
                            <Column field="" caption="Grupy dostępu"
                                    template={(value, row) => {
                                        if (row.groups.length > 0) {
                                            return <div><i className="fa fa-lock"></i> {row.groups.join(", ")}</div>
                                        }else{
                                            return <div className="lightgrey center"><i className="fa fa-times"></i></div>
                                        }
                                    }}
                            >
                                <Filter type="select" field="group" multiselect={true} content={this.props.accessGroups}/>
                            </Column>
                            <Column field="" caption="Zobacz"
                                    className="center darkgreen"
                                    noDefaultFilter
                                    template={() => <i className="fa fa-search"></i> }
                                    onClick={(row) => window.location.hash = this.props.baseURL + `/edit?key=${row.id}`}
                            />
                            <Column field="" caption="Usuń"
                                    className="center darkred"
                                    noDefaultFilter
                                    template={() => <i className="fa fa-times"></i> }
                                    onClick={this.handleDelete.bind(this)}
                            />
                        </Table>
                    </Panel>
                </Row>


            </div>
        )
    }
}