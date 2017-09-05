import React, {Component} from 'react';
import Navbar from 'frontend/src/ctrl/Navbar';
import Panel from 'frontend/src/ctrl/Panel';
import {Table, Column} from 'frontend/src/ctrl/Table';
import {Modal, confirm} from 'frontend/src/ctrl/Overlays';
import {BFile, BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from 'frontend/src/layout/BootstrapForm';
import Comm from 'frontend/src/lib/Comm';
import {Row} from 'frontend/src/layout/BootstrapLayout';


export default class ArrowViewComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            currEdited: false,
            dataLoading: false,
            currEditedData: {}
        };

        this.columns = [
            Column.id('id', 'Id'),
            Column.text('code', 'Kod'),
            Column.text('name', 'Nazwa'),




            Column.template('', () => <i className="fa fa-search"/>)
              .className('center')
              .onClick((row) => this.setState({currEdited: row.id})),
            Column.template('', () => <i className="fa fa-times"/>)
              .className('center darkred')
              .onClick((row) => this.handleDelete(row)),
        ];

    }

    handleDelete(row) {
        confirm(`Czy na pewno usunąć "${row.name}"?`).then(() => {
            Comm._post(this.props.baseURL + '/Language/delete', {key: row.id}).then(() => {
                this.props._notification(`Pomyślnie usunięto "${row.name}"`);
                this.table.load();
            });
        });
    }

    loadObjectData() {

        if (this.state.currEdited != -1) {
            this.setState({loading: true});
            Comm._post(this.props.baseURL + '/get', {key: this.state.currEdited})
              .then((response) => this.setState({currEditedData: response, loading: false}));
            ;
        } else {
            this.setState({currEditedData: {}});
        }
    }

    render() {
        let s = this.state;


        return (
          <div>
              <Navbar>
                  <span>Cms</span>
                  <span>Strony www</span>
              </Navbar>
              <Panel title="Lista dostępnych języków" toolbar={[
                  <a className="btn btn-primary btn-sm" onClick={() => this.setState({currEdited: -1})}>
                      <i className="fa fa-plus"></i> Dodaj
                  </a>

              ]}>
                  <Table
                    columns={this.columns}
                    remoteURL={this.props.baseURL + '/list'}
                    ref={(table) => this.table = table}
                  />

              </Panel>
              <Modal
                title={(s.currEdited == -1 ? 'Dodanie' : 'Edycja') + ' języka'}
                show={s.currEdited != false}
                onHide={() => this.setState({currEdited: false})}
                onShow={this.loadObjectData.bind(this)}
                showHideLink={true}

              >
                  <BForm
                    loading={this.state.loading}
                    data={this.state.currEditedData}
                    action={this.props.baseURL + '/save'}
                    namespace={'data'}
                    onSuccess={(e) => {
                        this.props._notification(this.state.currEditedData.name, 'Zapisano pomyślnie');
                        this.setState({currEdited: e.response[0]});
                        this.loadObjectData();
                        this.table.load();

                    }}
                  >
                      {(form) => <div style={{padding: 10, maxWidth: 500}} className="container">

                          <Row noGutters={false} >
                              <BText label="Nazwa" {...form('name')} />
                          </Row>
                          <Row noGutters={false} md={[10, 2]}>
                              <BSwitch label="Aktywny" {...form('active')} options={{0: 'Nie', 1: 'Tak'}}/>
                          </Row>
                          <Row noGutters={false}>
                              <BText label="Kod" {...form('code')} />
                              <BText label="Waluta" {...form('currency')} />
                          </Row>
                          <Row noGutters={false}>
                              <BText label="Obecny przelicznik" {...form('currency_value')} />
                              <BText label="Ostatnie sprawdzenie" editable={false} {...form('currency_update_time')} />
                          </Row>



                          <div className="hr-line-dashed"></div>
                          <button onClick={() => this.setState({currEdited: false})} className="btn btn-default pull-right">Anuluj</button>
                          <button className="btn btn-primary pull-right"><i className="fa fa-save"></i>Zapisz</button>

                      </div>}
                  </BForm>
              </Modal>
          </div>
        );
    }
}


