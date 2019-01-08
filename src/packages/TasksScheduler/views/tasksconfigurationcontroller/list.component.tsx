import * as React from "react";
import { IArrowViewComponentProps } from "frontend/lib/backoffice";
import { useContext, useEffect, useRef, useState } from "react";
import { CommandBar } from "frontend/lib/CommandBar";
import ColumnHelper, { Column, Table } from "frontend/lib/Table";
import { Navbar } from "frontend/lib/Navbar";
import { TabPane, Tabs } from "frontend/lib/Tabs";
import { Modal } from "frontend/lib/Modal";
import { BForm } from "frontend/lib/BForm";
import { BText } from "frontend/lib/BForm";
import { Icon } from "frontend/lib/Icon";
import { Comm } from "frontend/lib/lib";
import { LoadingIndicator } from "frontend/lib/LoadingIndicator";
import { Row } from "frontend/lib/Row";
import { PanelContext } from "frontend/lib/backoffice/PanelContext";
import { BSwitch } from "frontend/lib/BForm";
import { getPanelContext } from "frontend/lib/backoffice/PanelContext";

interface IViewProps extends IArrowViewComponentProps {}

export default function view(props: IViewProps) {
    const [isModalVisible, setModalVisible] = useState(false);
    const [editedData, setEditedData] = useState({});
    const [isRunning, setRunning] = useState(0);
    const [textToDisplay, setTextToDisplay] = useState("");
    const table = useRef(null);

    return (
        <>
            <CommandBar
                items={[
                    {
                        key: "f1",
                        label: "Add",
                        icon: "Add",
                        onClick: () => {
                            setEditedData({});
                            setModalVisible(true);
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
                        <Harmonogram
                            setTextToDisplay={setTextToDisplay}
                            table={table}
                            isRunning={isRunning}
                            setEditedData={setEditedData}
                            setModalVisible={setModalVisible}
                            setRunning={setRunning}
                        />
                    </TabPane>
                    {/*<TabPane title="Zadania do wykonania" icon="Task">
                        Tutaj zadania do wykonania
                    </TabPane>*/}
                    <TabPane title="Dziennik zdarzeń" icon="List">
                        <Log table={table} setTextToDisplay={setTextToDisplay} />
                    </TabPane>
                </Tabs>
            </div>
            {isModalVisible && (
                <AddModal setModalVisible={setModalVisible} editedData={editedData} viewProps={props} table={table} />
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

interface IModalProops {
    setModalVisible: (isVible: boolean) => any;
    editedData: any;
    viewProps: IViewProps;
    table: React.MutableRefObject<Table>;
}

const AddModal = function(props: IModalProops) {
    const formRef = useRef<BForm>(null);
    const panel = getPanelContext();
    const [cronInfo, setCronInfo] = useState<any>({});
    const data = props.editedData;
    if (data.schedule_config == undefined && props.editedData.cron_expression != undefined) {
        data.schedule_config = props.editedData.cron_expression.split(" ");
    }
    let updateCronInfo = (data: any) => {
        Comm._post(panel.baseURL + "/cron-schedule-info", {
            data,
        }).then((result) => {
            setCronInfo(result);
        });
    };
    useEffect(
        () => {
            updateCronInfo(data.schedule_config);
        },
        [data.schedule_config],
    );

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
                    ref={formRef}
                    action={panel.baseURL + "/add"}
                    onSuccess={() => {
                        props.table.current.load();
                        props.viewProps._notification("Dodano zadanie");
                        props.setModalVisible(false);
                    }}
                    onChange={(formEvent) => {
                        const data = formEvent.form.getData().schedule_config;
                        updateCronInfo(data);
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
                                            <pre>
                                                {cronInfo.map((date) => (
                                                    <div key={date}>{date}</div>
                                                ))}
                                            </pre>
                                        </div>
                                    )}
                                </Row>

                                <hr />

                                <Row noGutters={false}>
                                    <BText label="Zadanie" {...form("task")} />
                                </Row>
                                <Row noGutters={false}>
                                    <div style={{ textAlign: "right" }}>
                                        <a
                                            className="btn btn-primary "
                                            onClick={() => {
                                                formRef.current.submit(null);
                                            }}
                                        >
                                            Zapisz
                                        </a>
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

const Log = ({ table, setTextToDisplay }: any) => {
    const panel = useContext(PanelContext);

    return (
        <Table
            ref={table}
            remoteURL={panel.baseURL + "/list-log-data"}
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
    );
};

const Harmonogram = ({ table, isRunning, setRunning, setTextToDisplay, setEditedData, setModalVisible }: any) => {
    const panel = getPanelContext();
    return (
        <Table
            ref={table}
            remoteURL={panel.baseURL + "/list-data"}
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
                        <div>
                            {row.runDates.map((date: any) => (
                                <div key={date}>{date}</div>
                            ))}
                        </div>
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
                        Comm._get(panel.baseURL + "/run/" + row.id).then((result) => {
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
                        window.open(panel.baseURL + "/run/" + row.id);
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
    );
};
