import * as React from "react";
import { IArrowViewComponentProps } from "frontend/lib/backoffice";
import { useRef, useState } from "react";
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
import { Panel } from "frontend/lib/Panel";
import { PrintJSON } from "frontend/lib/PrintJSON";

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
                        <Table
                            ref={table}
                            remoteURL={props._baseURL + "/list-data"}
                            columns={[
                                Column.hidden("task"),
                                Column.id("id", "ID"),
                                Column.text("name", "Nazwa"),
                                Column.text("shelude_config", "Harmonogram"),
                                Column.text("last_run", "Ostatnie wykonanie"),
                                Column.template("Plan wykonania", (val, row) => {
                                    return (
                                        <div>
                                            {row.runDates.map((date) => (
                                                <div key={date}>{date}</div>
                                            ))}
                                        </div>
                                    );
                                }),
                                Column.template("Edytuj", (val, row) => {
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
                                    .className("center"),
                                Column.template("Edytuj", () => <Icon name="Edit" />)
                                    .onClick((row) => {
                                        setEditedData(row);
                                        setModalVisible(true);
                                    })
                                    .className("center"),
                            ]}
                        />
                    </TabPane>
                    <TabPane title="Dziennik zdarzeń" icon="List">
                        <Table
                            ref={table}
                            remoteURL={props._baseURL + "/list-log-data"}
                            columns={[
                                Column.id("id", "Id"),
                                Column.text("C:name", "Typ"),
                                Column.date("started", "Rozpoczęto"),
                                Column.date("finished", "Zakończono"),
                                Column.number("time", "Trwała").template((val) => parseInt(val, 10) / 1000 + " s"),
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
                        <pre style={{ margin: 0 }}> {textToDisplay}</pre>
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
    return (
        <Modal
            show={true}
            onHide={() => props.setModalVisible(false)}
            title="Edycja zadania"
            showHideLink={true}
            top={200}
        >
            <div style={{ padding: 10, width: 500 }}>
                <BForm
                    data={props.editedData}
                    ref={formRef}
                    action={props.viewProps._baseURL + "/add"}
                    onSuccess={() => {
                        props.table.current.load();
                        props.viewProps._notification("Dodano zadanie");
                        props.setModalVisible(false);
                    }}
                    namespace="data"
                >
                    {(form) => {
                        return (
                            <>
                                <BText label="Nazwa" {...form("name")} autoFocus={true} />
                                <BText label="Konfiguracja cron" {...form("shelude_config")} />
                                <BText label="Zadanie" {...form("task")} />
                                <div style={{ textAlign: "right" }}>
                                    <a
                                        className="btn btn-primary "
                                        onClick={() => {
                                            formRef.current.submit();
                                        }}
                                    >
                                        Zapisz
                                    </a>
                                    <a className="btn btn-default" onClick={() => props.setModalVisible(false)}>
                                        Anuluj
                                    </a>
                                </div>
                            </>
                        );
                    }}
                </BForm>
            </div>
        </Modal>
    );
};
