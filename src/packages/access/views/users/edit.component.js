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

//import {Table, Column, Filter} from 'ctrl/Table'



export default class ArrowViewComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            formData: {...props.user, password: ''},
            response: {}
        };
    }

    handleFormChange(e) {
        this.forceUpdate();
        let data = this.refs.form.getData();

        this.setState({formData: data});
    }

    handleFormSuccess(e) {
        alertify.success('Zapisano');
    }

    handleFormSubmit(e) {
        this.forceUpdate();
        let data = this.refs.form.getData();
        $.getJSON(this.props.baseURL + "/save", {data: data, key: data.id}, (ret) => {


            this.setState({response: ret});
        })
        console.log()
    }

    render() {
        let data = this.state.formData || {};
        data.selectedGroups = this.props.selectedGroups;
        return (
            <div>
                <Navbar>
                    <span>System</span>
                    <a href={'#' + this.props.baseURL + '/list'}>Użytkownicy</a>
                    <span>{this.props.user ? this.props.user.login : 'Dodaj'}</span>
                </Navbar>
                <Row md={[6]}>
                    <Panel title={'Formularz ' + (this.props.user ? "edycji" : 'dodania') + " użytkownika"}>
                        <BForm
                            ref="form"
                            data={data}
                            action={this.props.baseURL + "/save"}
                            onSuccess={this.handleFormSuccess.bind(this)}
                            //onSubmit={this.handleFormSubmit.bind(this)}
                            onChange={this.handleFormChange.bind(this)}
                        >
                            <BText label="Login" name="login"  />
                            <BSwitch label="Konto aktywne" name="active"  inline={true} options={{0: "Nie", 1: "Tak"}}/>
                            <BCheckboxGroup label="Grupy dostępu" name="selectedGroups" inline={false} options={this.props.groups || []}/>
                            <BText label="Email" type="email" name="email"/>
                            <div className="hr-line-dashed"></div>
                            <BText label="Hasło" type="password" name="password" placeholder={data.id ? 'Podaj hasło aby zmienić na nowe' : ''}/>

                            <button type="submit" className="btn btn-primary"> Zapisz</button>


                        </BForm>
                    </Panel>
                    {/*<Panel title="Data">
                        <pre>
                            {JSON.stringify(this.props.history, null, 2)}
                            <hr/>
                            {JSON.stringify(this.state.response, null, 2)}
                            <hr/>
                            {JSON.stringify(this.state.formData, null, 2)}
                        </pre>
                    </Panel>*/}
                </Row>
                {/*<Panel title="Historia logowań">
                    <SimpleTable fromFlatObject={this.props.user}></SimpleTable>
                </Panel>*/}
            </div>
        )
    }
}