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
            langToDownload: false
        };

        this.columns = [
            Column.id('id', 'Id'),
            Column.text('lang', 'Język [kod]'),
            Column.map('lang', 'Język', this.props.language),
            Column.text('value', 'Wartość'),
            Column.text('original', 'Orginał'),
            Column.text('module', 'Moduł'),

            Column.template('', () => <i className="fa fa-times"/>)
              .className('center darkred')
              .onClick((row) => this.handleDelete(row))
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


    render() {
        let s = this.state;


        return (
          <div>
              <Navbar>
                  <span>Cms</span>
                  <span>Tłumaczenia</span>
              </Navbar>
              <Panel title="Lista dostępnych tłumaczeń" toolbar={[
                  <a className="btn btn-primary btn-sm" onClick={() => this.setState({langToDownload: 'xx'})}>
                      <i className="fa fa-file-excel-o"></i> Pobierz arkusz
                  </a>,
                  <a className="btn btn-primary btn-sm" onClick={() => this.setState({currEdited: -1})}>
                      <i className="fa fa-upload"></i> Załaduj plik
                  </a>
              ]}>
                  <Table
                    columns={this.columns}
                    remoteURL={this.props.baseURL + '/list'}
                    ref={(table) => this.table = table}
                  />

              </Panel>


              <Modal
                title={'Pobranie pliku języka'}
                show={s.langToDownload!=false}
                onHide={() => this.setState({langToDownload: false})}
                showHideLink={true}
              >
                  <div style={{padding: 10, maxWidth: 500}} className="container">
                      <BSelect
                        label={'Język do pobrania'}
                        value={this.state.langToDownload}
                        options={{xx:'--Wybierz język ---', ...this.props.language}}
                        onChange={(e) => this.setState({ langToDownload: e.target.value })}
                      />

                      {this.state.langToDownload != "xx" && <button className="btn btn-primary pull-right"> <i className="fa fa-download"></i> Pobierz</button>}

                  </div>
              </Modal>

          </div>
        );
    }
}


