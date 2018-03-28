import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import {Row} from "frontend/src/layout/BootstrapLayout";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {Panel} from "frontend/src/ctrl/Panel";
import {Table} from "frontend/src/ctrl/Table";
import {ColumnHelper as Column} from "frontend/src/ctrl/table/ColumnHelper";
import Comm from "frontend/src/lib/Comm";
import {LoadingIndicator} from "frontend/src/ctrl/LoadingIndicator";
import Icon from "frontend/src/ctrl/Icon";
import {Modal} from "frontend/src/ctrl/Overlays";

interface IProps extends IArrowViewComponentProps {
    options: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {


    table: Table;

    constructor(props) {
        super(props);
        this.state = {
            currSync: false,
            currTextPresentation: false
        };

    }

    public handleSync(what) {
        this.setState({currSync: what});
        Comm._get(this.props._baseURL + `/runSynch/${what.actionName}`).then(() => {
            this.props._notification("Synchronizacja", "Zakończono");
            this.table.load();
            this.setState({currSync: false});
        });
    }

    handleOpen(what: any): any {
        window.open(this.props._basePath + this.props._baseURL + `/runSynch/${what.actionName}/1`);
    }

    public render() {
        const p = this.props;
        const {currSync} = this.state;

        return <div>

            <Navbar>
                <a>CRM</a>
                <a>Komunikacja</a>
                <a>Synchronizacja</a>
            </Navbar>

            <div className="panel-body-top-fix">
                <Row md={[5, 7]} noGutters={false}>
                    <Panel title={"Dostępne synchronizacje"} noPadding={true} icon={"Sync"}>
                        <ul className={"tasks-list"}>
                            {p.options.map((e) => <li key={e.actionName}>
                                    <a onClick={() => this.handleSync(e)}>{e.label}</a>
                                    {e.subTasks && <ul>
                                        {e.subTasks.map((sub) => <li key={sub.actionName}>
                                            <a>
                                                <span onClick={() => this.handleSync(sub)}>{sub.label}</span>
                                                <span onClick={() => this.handleOpen(sub)} style={{float: 'right'}}><Icon name={"OpenInNewWindow"}/> </span>
                                            </a>
                                        </li>}
                                    </ul>}
                                </li>,
                            )}
                        </ul>
                    </Panel>

                    <Panel title={currSync && currSync.label} noPadding={true}>
                        {/*{currSync && <div>
                            <iframe style={{width: "100%", height: 500}} src={this.props._baseURL + "/" + currSync.action + "?" + currSync.rand}></iframe>
                        </div>}*/}
                        {currSync && <LoadingIndicator/>}
                        <Table
                            ref={(el) => this.table = el}
                            remoteURL={this.props._baseURL + "/asyncLog"}
                            columns={[
                                Column.id("id", "Id"),
                                Column.text("type", "Typ"),
                                Column.date("started", "Rozpoczęto"),
                                Column.date("finished", "Zakończono"),
                                Column.number("time", "Trwała")
                                    .template((val) => (parseInt(val, 10) / 1000) + " s"),
                                Column.text("errors", "Błędy")
                                    .template((val) => {
                                        if (val.length > 0) {
                                            return <a className={"sync-error-icon"}><Icon name={"Error"}/></a>
                                        }
                                    })
                                    .className("center")
                                    .onClick((row) => {
                                        this.setState({currTextPresentation: row.errors});
                                    })
                                ,
                                Column.text("output", "Info")
                                    .template((val) => {
                                        if (val.length > 0) {
                                            return <a className={"sync-info-icon"}><Icon name={"InfoSolid"}/></a>
                                        }
                                    })
                                    .className("center")
                                    .onClick((row) => this.setState({currTextPresentation: row.output}))
                                ,

                                /*Column.number("memory", "Pamięć")
                                    .template((val) => (parseInt(val, 10) / 1000) + " s"),*/
                            ]}
                        />

                    </Panel>
                </Row>
            </div>

            <Modal show={this.state.currTextPresentation != false}
                   onHide={() => this.setState({currTextPresentation: false})}

            >
                <div style={{padding: 10}}>
                    <pre>{this.state.currTextPresentation}</pre>
                </div>
            </Modal>

        </div>;
    }
}
