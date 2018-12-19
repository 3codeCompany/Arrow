import Navbar from "frontend/src/ctrl/Navbar";
import Panel from "frontend/src/ctrl/Panel";

import * as React from "react";
import { IArrowViewComponentProps } from "frontend/src/lib/PanelComponentLoader";
import { BForm, BSwitch, BContainer, BSelect, BText } from "frontend/src/layout/BootstrapForm";
import { CommandBar } from "frontend/src/ctrl/CommandBar";
import Comm from "frontend/src/lib/Comm";
import { Row } from "frontend/src/layout/BootstrapLayout";
import { Copyable } from "frontend/src/ctrl/Copyable";
import Icon from "frontend/src/ctrl/Icon";
import * as path from "path";
import Comm from "frontend/src/lib/Comm";

interface IProps extends IArrowViewComponentProps {
    ARROW_DEV_MODE: string;
    routes: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    constructor(props) {
        super(props);
        this.state = {
            data: null,
            tables: null,
            selectedTable: null,
            condField: null,
            selectedValue: null,
            newValue: null,
            selectedEsoTable: null,
            esoData: null,
        };
    };

    componentDidMount() {
        this.getTables();
    }

    public getTables() {
        Comm._get(this.props._baseURL + "/getTables")
            .then((r) => {
                console.log(r, "TAbles result");
                this.setState({
                    tables: r.result
                })
            })
            .catch((e) => {
                console.error(e, "response tables data error");
            })
    }

    public getData() {
        Comm._post(this.props._baseURL + "/getData", {table: this.state.selectedTable, condField: this.state.condField})
            .then((r) => {
                console.log(r, "RESP DATA");
                this.setState({
                    data: r.result
                })
            })
            .catch((e) => {
                console.error(e, "response data error");
            })
    }

    public prepareDataToPrint(data) {
        let str = "";
        data &&
        data.forEach((e, k) => {
            str += e.id + ",";
        })
        console.log(data, "data thewer");

        const rd = JSON.stringify(data, null, 2);
        return str;
    }

    public parseData(data) {
        const rd = JSON.parse(data);
        return rd;
    }

    public addValue(data, key, value) {
        data.forEach((e, k) => {
            data[k][key] = value;
        })
        this.setState({
            data,
        })
    }

    public resetValue(data, key, value, cond = null) {
        let col;
        let opperator;
        let val;
        if (cond) {
            const std = cond.split(" ");
            col = std[0];
            opperator = std[1];
            val = std[2];
            const opperators = {
                "=": (a, b) => a == b,
                ">": (a, b) => a < b,
                "<": (a, b) => a > b,
            }
            data.forEach((e, k) => {
                if (opperators[opperator](e[col], val)) {
                    data[k][key] = value;
                    this.setState({data})
                }
            })
            return;
        }
        
        data.forEach((e, k) => {
            data[k][key] = value;
        })
        this.setState({data})
    }
    
    public getValues(data) {
        let r = {
            pureValues: [],
            forSelect: [],
        };
        Object.keys(data[0]).forEach((ok, kk) => {
            r.forSelect.push({
                value: ok, label: ok,
            })
        })
        return r;
    }

    public getEsoData() {
        Comm._post(this.props._baseURL + "/getEsoData", {table: this.state.selectedTable})
            .then((r) => {
                this.setState({
                    esoData: r.result,
                })
            })
            .catch((e) =>console.error(e, "Error save data"))
    }

    public getEsoColumns(data) {
        Comm._post(this.props._baseURL + "/getColumns", {table: this.state.selectedTable})
            .then((r) => {
                const cols = r.result;
                let fsFields = [];

                Object.keys(data[0]).forEach((ok, kk) => {
                    fsFields.push(ok);
                })

                cols.filter((e, k) => {
                    if (!fsFields.includes(e)) {
                        data.forEach((de, dk) => {
                            data[dk][e] = null;
                        })
                    }
                })

                this.setState({
                    data,
                })
            })
            .catch((e) =>console.error(e, "Error save data"))
    }

    public saveData(data) {
        let dataPart = data.slice(0, 500)



        Comm._post(this.props._baseURL + "/setData", {table: this.state.selectedTable, data: dataPart})
            .then((r) => console.log(r))
            .catch((e) =>console.error(e, "Error save data"))
    }

    public mergeTables(data1 /*data finalsale*/, data2 /*data esotiq*/) {
        let finalsaleIds = [];

        data1.map((fe, fk) => {
            data2.forEach((ee, ek) => {
                if (fe.group_key == ee.group_key && fe.color == ee.color) {
                    finalsaleIds.push(fe.id);
                    return;
                }
            })
        })

        finalsaleIds = finalsaleIds.filter((e, k) => finalsaleIds.indexOf(e) == k)

        this.setState({
            merged: finalsaleIds,
        })
        console.log(finalsaleIds, finalsaleIds.length);
    }

    public render() {
        const {data, tables} = this.state;

        return (
            <div>
                <Navbar>
                    <span>Utils</span>
                    <span>Migration service</span>
                </Navbar>
                <div className={"panel-body-margins"}>
                    {tables &&
                        <div>
                            <BSelect
                                options={tables}
                                label={"Tabela"}
                                emptyValueBlock={true}
                                value={this.state.selectedTable}
                                onChange={(val) => {
                                    this.setState({
                                        selectedTable: val.value,
                                    })
                                }
                                }
                            />

                            <BText
                                label={"Condition where"}
                                value={this.state.condField}
                                onChange={(val) => {
                                    this.setState({
                                        condField: val.value,
                                    })
                                }
                                }
                            />
                        </div>
                    }

                    <Row>
                        <button
                            className={"btn btn-primary"}
                            onClick={() => this.getData()}
                        >
                            Get request data
                        </button>

                        <button
                            className={"btn btn-primary"}
                            onClick={() => this.getEsoColumns(data)}
                        >
                            Add Eso columns
                        </button>

                        <button
                            className={"btn btn-primary"}
                            onClick={() => this.getEsoData(data)}
                        >
                            Get eso table data
                        </button>

                        <button
                            className={"btn btn-primary"}
                            onClick={() => this.mergeTables(data, this.state.esoData)}
                        >
                            Merge tables
                        </button>

                        <button
                            className={"btn btn-primary"}
                            onClick={() => this.resetValue(data, "id", null)}
                        >
                            Reset data id's
                        </button>

                        <div>
                            <button
                                className={"btn btn-primary"}
                                onClick={() => this.resetValue(data, this.state.selectedValue, this.state.newValue, this.state.newColumnValueCondition)}
                            >
                                Reset column data
                            </button>
                            {data &&
                                <BSelect
                                    options={this.getValues(data).forSelect}
                                    label={"Column"}
                                    emptyValueBlock={true}
                                    value={this.state.selectedValue}
                                    onChange={(val) => {
                                        this.setState({
                                            selectedValue: val.value,
                                        })
                                    }
                                    }
                                />
                            }
                            {this.state.selectedValue &&
                                <BText
                                    label={"New value"}
                                    value={this.state.newValue}
                                    onChange={(val) => {
                                        this.setState({
                                            newValue: val.value,
                                        })
                                    }
                                    }
                                />
                            }
                            {this.state.selectedValue &&
                            <BText
                                label={"Condition"}
                                value={this.state.newColumnValueCondition}
                                onChange={(val) => {
                                    this.setState({
                                        newColumnValueCondition: val.value,
                                    })
                                }
                                }
                            />
                            }
                        </div>

                        <div>
                            <button
                                className={"btn btn-primary"}
                                onClick={() => this.addValue(data, this.state.newColumn, this.state.newColumnValue)}
                            >
                                Add column data
                            </button>
                            {data &&
                            <BText
                                label={"New column"}
                                value={this.state.newColumn}
                                onChange={(val) => {
                                    this.setState({
                                        newColumn: val.value,
                                    })
                                }
                                }
                            />
                            }

                            {this.state.newColumn &&
                            <BText
                                label={"New column value"}
                                value={this.state.newColumnValue}
                                onChange={(val) => {
                                    this.setState({
                                        newColumnValue: val.value,
                                    })
                                }
                                }
                            />
                            }
                        </div>

                        {this.state.selectedTable &&
                        <div>
                            <button
                                className={"btn btn-primary"}
                                onClick={() => this.saveData(data)}
                            >
                                Save data to db
                            </button>
                            <BText
                                label={"Table"}
                                value={this.state.selectedTable}
                                onChange={(val) => {
                                this.setState({
                                    selectedTable: val.value,
                                })}
                                }
                            />
                        </div>
                        }

                    </Row>


                    <Row>
                        {this.state.merged &&
                            <div>
                                <h4>Merged:</h4>
                                <p>Count: {this.state.merged.length}</p>

                                <pre>
                                    {this.prepareDataToPrint(this.state.merged)}
                                </pre>
                            </div>
                        }

                        <div>
                            <h4>Finalsale</h4>
                            <div>Count: {data && data.length}</div>
                            {data && <p>rdy</p>}
                            <pre>
                                {this.prepareDataToPrint(data)}
                            </pre>
                        </div>

                        <div>
                            <h4>Esotiq</h4>
                            <div>Count: {this.state.esoData && this.state.esoData.length}</div>
                            {this.state.esoData && <p>rdy</p>}
                            {/*<pre>*/}
                                {/*{this.prepareDataToPrint(this.state.esoData)}*/}
                            {/*</pre>*/}
                        </div>
                    </Row>

                </div>
            </div>
        );
    }
    
}
