import React, {Component} from 'react';
import Navbar from 'frontend/src/ctrl/Navbar';
import Panel from 'frontend/src/ctrl/Panel';
import {Table, Column} from 'frontend/src/ctrl/Table';
import {Modal, confirm} from 'frontend/src/ctrl/Overlays';
import {BFile, BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from 'frontend/src/layout/BootstrapForm';
import Comm from 'frontend/src/lib/Comm';
import {Row} from 'frontend/src/layout/BootstrapLayout';
import {CommandBar} from 'frontend/src/ctrl/CommandBar';

export default class ArrowViewComponent extends Component {
    constructor(props) {
        super(props);
        this.state = {
            langToDownload: false,
            search: ''
        };

        this.columns = [
            Column.id('id', 'Id'),
            Column.text('lang', 'Kod języka'),
            Column.map('lang', 'Język', this.props.language),
            Column.text('value', 'Wartość')
                .template((val, row) => <div>
                    {row.loading && <div><i className="fa fa-spinner fa-spin"/></div>}
                    {row.edited === true && [
                        <textarea type="text" style={{width: '100%', display: 'block'}} onChange={(e) => row.changedText = e.target.value} defaultValue={val}></textarea>,
                        <div>
                            <a onClick={this.handleRowChanged.bind(this, row)} className="btn btn-primary btn-xs btn-block pull-left" style={{margin: 0, width: '50%'}}>Zapisz</a>
                            <a onClick={(e) => {
                                e.stopPropagation();
                                row.edited = false;
                                this.table.forceUpdate();
                            }} className="btn btn-default btn-xs btn-block pull-right" style={{margin: 0, width: '50%'}}>Anuluj</a>
                        </div>
                    ]}
                    {!row.loading && !row.edited && <div>{val}</div>}
                </div>)
                .set({styleTemplate: (row) => row.edited ? {padding: 0} : {}})
                .onClick((row) => {
                    row.edited = true;
                    row.changedText = row.value;
                    this.table.forceUpdate();
                })
            ,
            Column.text('original', 'Orginał'),
            Column.text('module', 'Moduł'),

            Column.template('', () => <i className="ms-Icon ms-Icon--Delete" style={{fontSize: 14}}/>)
                .className('center darkred')
                .onClick((row) => this.handleDelete(row))
        ];

    }
    handleRowChanged(row, e) {
        e.stopPropagation();
        row.loading = true;
        row.edited = false;
        this.table.forceUpdate();
        Comm._post(this.props.baseURL + '/inlineUpdate', {key: row.id, newValue: row.changedText}).then(() => {
            this.props._notification('Pomyślnie zmodyfikowano element');
            row.value = row.changedText;
            row.loading = false;
            this.table.forceUpdate();
        });

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
                    <span>Lista dostępnych tłumaczeń</span>
                </Navbar>
                <Panel>
                    <CommandBar
                        isSearchBoxVisible={true}
                        onSearch={(val) => {
                            this.setState({search: val}, () => this.table.load());
                        }}
                        items={[
                            {label: 'Pobierz arkusz', icon: 'Download', onClick: () => this.setState({langToDownload: 'xx'})},
                            {label: 'Załaduj plik', icon: 'Upload', onClick: () => this.setState({currEdited: -1})},
                        ]}
                    />
                    <Table
                        columns={this.columns}
                        remoteURL={this.props.baseURL + '/list'}
                        ref={(table) => this.table = table}
                        additionalConditions={{search: this.state.search}}
                    />

                </Panel>


                <Modal
                    title={'Pobranie pliku języka'}
                    show={s.langToDownload != false}

                    onHide={() => this.setState({langToDownload: false})}
                    showHideLink={true}
                    top={100}
                >
                    <div style={{padding: 10, maxWidth: 500}} className="container">
                        <BSelect
                            label={'Język do pobrania'}
                            value={this.state.langToDownload}
                            options={{xx: '--Wybierz język ---', ...this.props.language}}
                            onChange={(e) => this.setState({langToDownload: e.value})}
                        />

                        {this.state.langToDownload != 'xx' && <button className="btn btn-primary pull-right"><i className="fa fa-download"></i> Pobierz</button>}

                    </div>
                </Modal>

            </div>
        );
    }
}


