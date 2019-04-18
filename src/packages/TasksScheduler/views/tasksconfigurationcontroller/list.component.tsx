import * as React from "react";
import Navbar from "frontend/src/ctrl/Navbar";
import { Column, Table } from "frontend/src/ctrl/Table";

import { confirm, Modal } from "frontend/src/ctrl/Overlays";
import { BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig } from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";
import { Row } from "frontend/src/layout/BootstrapLayout";

import { IArrowViewComponentProps } from "frontend/src/lib/PanelComponentLoader";
import { CommandBar } from "frontend/src/ctrl/CommandBar";
import { Datasource } from "frontend/src/lib/Datasource";
import { LoaderContainer } from "frontend/src/ctrl/LoaderContainer";
import Icon from "frontend/src/ctrl/Icon";
import { Tabs, TabPane } from "frontend/src/ctrl/Tabs";
import { LoadingIndicator } from "frontend/src/ctrl/LoadingIndicator";

interface IProps extends IArrowViewComponentProps {
    groups: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    private columns: Column[];
    private table: Table;
    private table2: Table;

    constructor(props) {
        super(props);
        this.state = {
            isModalVisible: false,
            editedData: {},
            isRunning: 0,
            textToDisplay: "",
        };

        this.columns = [];
    }

    public render() {
        const s = this.state;
        const props = this.props;

        const setEditedData = (val) => this.setState({ editedData: val });
        const setTextToDisplay = (val) => {
            this.setState({ textToDisplay: val });
        };
        const setModalVisible = (val) => {
            this.setState({ isModalVisible: val });
        };
        const setRunning = (val) => {
            this.setState({ isRunning: val });
        };

        const { isModalVisible, editedData, isRunning, textToDisplay } = this.state;

        return (
            <>
                <CommandBar
                    items={[
                        {
                            key: "f1",
                            label: "Add",
                            icon: "Add",
                            onClick: () => {
                                this.setState({ isModalVisible: true, editedData: {} });
                            },
                        },
                    ]}
                />
                <Navbar>
                    <span>System</span>
                    <span>Harmonogram zadań</span>
                </Navbar>
                <div className="panel-body-margins">
                    <Tabs>
                        <TabPane title="Harmonogram" icon="Clock">
                            <Table
                                ref={(el ) => this.table = el}
                                remoteURL={props._baseURL + "/list-data"}
                                columns={[
                                    Column.hidden("task"),
                                    Column.hidden("max_execute_time"),
                                    Column.id("id", "ID").width(50),
                                    Column.bool("active", "Aktywne").width(90),
                                    Column.text("name", "Nazwa"),
                                    Column.text("cron_expression", "Harmonogram"),
                                    Column.text("last_run", "Ostatnie wykonanie"),
                                    Column.template("Plan wykonania", (val, row) => {
                                        return (
                                            <div>{row.runDates.map((date: any) => <div key={date}>{date}</div>)}</div>
                                        );
                                    }),
                                    Column.template("Run", (val, row) => {
                                        if (isRunning == row.id) {
                                            return "---";
                                        }
                                        return <Icon name="Play" />;
                                    })
                                        .onClick((row, columnData, RowComponent) => {
                                            setRunning(row.id);
                                            RowComponent.forceUpdate();
                                            Comm._get(props._baseURL + "/run/" + row.id).then((result) => {
                                                setRunning(0);
                                                setTextToDisplay(
                                                    "Time: " +
                                                        parseInt(result.log.time, 10) / 1000 +
                                                        " s\n\n" +
                                                        result.log.output +
                                                        "\n" +
                                                        result.log.errors,
                                                );
                                            });
                                        })
                                        .className("center")
                                        .width(80),
                                    Column.template("Open & Run", (val, row) => {
                                        if (isRunning == row.id) {
                                            return "---";
                                        }
                                        return <Icon name="OpenInNewWindow" />;
                                    })
                                        .onClick((row, columnData, RowComponent) => {
                                            window.open(props._baseURL + "/run/" + row.id);
                                        })
                                        .className("center")
                                        .width(80),
                                    Column.template("Edytuj", () => <Icon name="Edit" />)
                                        .onClick((row) => {
                                            setEditedData(row);
                                            setModalVisible(true);
                                        })
                                        .className("center"),
                                ]}
                            />
                        </TabPane>
                        {/*<TabPane title="Zadania do wykonania" icon="Task">
                        Tutaj zadania do wykonania
                    </TabPane>*/}
                        <TabPane title="Dziennik zdarzeń" icon="List">
                            <Table
                                ref={(el ) => this.table2 = el}
                                remoteURL={this.props._baseURL + "/list-log-data"}
                                columns={[
                                    Column.id("id", "Id").width(80),
                                    Column.id("pid", "PID").width(80),
                                    Column.text("C:name", "Typ"),
                                    Column.date("started", "Rozpoczęto"),
                                    Column.date("finished", "Zakończono").template((val) => {
                                        if (val == "0000-00-00 00:00:00") {
                                            return "----";
                                        }
                                        return val;
                                    }),
                                    Column.number("time", "Trwała/Trwa").template((val, row) => {
                                        if (row.finished == "0000-00-00 00:00:00") {
                                            return "---";
                                        }
                                        return parseInt(val, 10) / 1000 + " s";
                                    }),
                                    Column.text("errors", "Błędy")
                                        .template((val) => {
                                            if (val.length > 0) {
                                                return (
                                                    <a className={"sync-error-icon"}>
                                                        <Icon name={"Error"} />
                                                    </a>
                                                );
                                            }
                                        })
                                        .className("center")
                                        .onClick((row) => {
                                            setTextToDisplay(row.errors);
                                        }),
                                    Column.text("output", "Info")
                                        .template((val) => {
                                            if (val.length > 0) {
                                                return (
                                                    <a className={"sync-info-icon"}>
                                                        <Icon name={"InfoSolid"} />
                                                    </a>
                                                );
                                            }
                                        })
                                        .className("center")
                                        .onClick((row) => setTextToDisplay(row.output)),

                                    /*Column.number("memory", "Pamięć")
                                    .template((val) => (parseInt(val, 10) / 1000) + " s"),*/
                                ]}
                            />
                        </TabPane>
                    </Tabs>
                </div>
                {isModalVisible && (
                    <AddModal
                        setModalVisible={setModalVisible}
                        editedData={editedData}
                        viewProps={props}
                        table={this.table}
                        panel={props}
                    />
                )}
                {isRunning != 0 && (
                    <Modal show={true}>
                        <LoadingIndicator text="Running" />
                    </Modal>
                )}
                {textToDisplay != "" && (
                    <Modal show={true} onHide={() => setTextToDisplay("")}>
                        <div style={{ maxWidth: "80vw", margin: 10 }}>
                            <pre style={{ margin: 0 }}>{textToDisplay}</pre>
                        </div>
                    </Modal>
                )}
            </>
        );
    }
}

interface IModalProops {
    setModalVisible: (isVible: boolean) => any;
    editedData: any;
    viewProps: any;
    table: React.MutableRefObject<Table>;
    panel: IProps;
}

class AddModal extends React.Component<IModalProops, any> {
    private formRef = null;

    constructor(props) {
        super(props);
        this.state = {
            data: props.editedData,
            cronInfo: {},
        };
    }

    componentDidMount(): void {
        this.updateCronInfo(this.state.data.schedule_config);
    }

    updateCronInfo = (data: any) => {
        Comm._post(this.props.panel._baseURL + "/cron-schedule-info", {
            data,
        }).then((result) => {
            this.setState({ cronInfo: result });
        });
    };

    public render = () => {

        const props = this.props;
        const { cronInfo } = this.state;
        const data = this.state.data;

        if (data.schedule_config == undefined && props.editedData.cron_expression != undefined) {
            data.schedule_config = props.editedData.cron_expression.split(" ");
        }

        /*useEffect(
            () => {
                updateCronInfo(data.schedule_config);
            },
            [data.schedule_config],
        );*/
        //console.log(props.table, "to jest ");

        return (
            <Modal
                show={true}
                onHide={() => props.setModalVisible(false)}
                title="Edycja zadania"
                showHideLink={true}
                top={200}
            >
                <div style={{ padding: "10px 3px", width: 500 }}>

                    <BForm
                        data={data}
                        ref={this.formRef}
                        action={this.props.panel._baseURL + "/add"}
                        onSuccess={() => {
                            props.table.load();
                            props.panel._notification("Dodano zadanie");
                            props.setModalVisible(false);
                        }}
                        onChange={(formEvent) => {
                            const data = formEvent.form.getData().schedule_config;
                            this.updateCronInfo(data);
                        }}
                        namespace="data"
                    >
                        {(form) => {
                            return (
                                <>
                                    <Row noGutters={false}>
                                        <BText label="Nazwa" {...form("name")} autoFocus={true} />
                                    </Row>
                                    <Row noGutters={false}>
                                        <BSwitch
                                            label="Aktywny"
                                            {...form("active", 1)}
                                            options={[{ value: 0, label: "Nie" }, { value: 1, label: "Tak" }]}
                                        />

                                        <BText label="Maksymalny czas (s)" {...form("max_execute_time", "500")} />
                                    </Row>
                                    <hr />
                                    <Row noGutters={false}>
                                        <label>Konfiguracja cron</label>
                                    </Row>
                                    <Row noGutters={false}>
                                        <BText label="Min" {...form("schedule_config[0]", "*")} />
                                        <BText label="Hour" {...form("schedule_config[1]", "*")} />
                                    </Row>
                                    <Row noGutters={false}>
                                        <BText label="Day (month)" {...form("schedule_config[2]", "*")} />
                                        <BText label="Month" {...form("schedule_config[3]", "*")} />
                                    </Row>
                                    <Row noGutters={false}>
                                        <BText label="Day (week)" {...form("schedule_config[4]", "*")} />
                                    </Row>
                                    <Row noGutters={false}>
                                        {cronInfo.error != undefined && (
                                            <div style={{ backgroundColor: "darkred", color: "white", padding: 10 }}>
                                                {cronInfo.error}
                                            </div>
                                        )}
                                    </Row>
                                    <Row noGutters={false}>
                                        <pre>
                                            <div>* any value</div>
                                            <div>, value list separator</div>
                                            <div>- range of values</div>
                                            <div>/ step values</div>
                                        </pre>
                                    </Row>
                                    <Row noGutters={false}>
                                        {Array.isArray(cronInfo) && (
                                            <div style={{ marginTop: 10 }}>
                                                <b>Kolejne 10 wykonań:</b>
                                                <pre>{cronInfo.map((date) => <div key={date}>{date}</div>)}</pre>
                                            </div>
                                        )}
                                    </Row>

                                    <hr />

                                    <Row noGutters={false}>
                                        <BText label="Zadanie" {...form("task")} />
                                    </Row>
                                    <Row noGutters={false}>
                                        <div style={{ textAlign: "right" }}>
                                            <button
                                                className="btn btn-primary "
                                                type="submit"
                                            >
                                                Zapisz
                                            </button>
                                            <a className="btn btn-default" onClick={() => props.setModalVisible(false)}>
                                                Anuluj
                                            </a>
                                        </div>
                                    </Row>
                                </>
                            );
                        }}
                    </BForm>
                </div>
            </Modal>
        );
    };
}
