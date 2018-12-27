import * as React from "react";
import { IArrowViewComponentProps } from "frontend/lib/backoffice";
import { useState } from "react";
import { CommandBar } from "frontend/lib/CommandBar";
import { Column, Table } from "frontend/lib/Table";
import { Navbar } from "frontend/lib/Navbar";
import { TabPane, Tabs } from "frontend/lib/Tabs";
import { Modal } from "frontend/lib/Modal";
import { BForm } from "frontend/lib/BForm";
import { BText } from "frontend/lib/BForm";

interface IViewProps extends IArrowViewComponentProps {}

export default function view(props: IViewProps) {
    const [isModalVisible, setModalVisible] = useState(false);
    const [editedData, setEditedData] = useState({});

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
                    <TabPane title="Harmonogram">
                        <Table
                            remoteURL={props._baseURL + "/list-data"}
                            columns={[
                                Column.id("id", "ID"),
                                Column.text("name", "Nazwa"),
                                Column.text("scheduler", "Harmonogram"),
                                Column.text("last_run", "Ostatnie wykonanie"),
                                Column.text("last_run", "Wykona się za"),
                                Column.text("last_run", "Wykonaj"),
                                Column.text("last_run", "Edytuj").onClick((row) => {
                                    setEditedData(row);
                                    setModalVisible(true);
                                }),
                            ]}
                        />
                    </TabPane>
                    <TabPane title="Dziennik zdarzeń">lista bla</TabPane>
                </Tabs>
            </div>
            {isModalVisible && <AddModal setModalVisible={setModalVisible} editedData={editedData} />}
        </>
    );
}

interface IModalProops {
    setModalVisible: (isVible: boolean) => any;
    editedData: any;
}

const AddModal = function(props: IModalProops) {
    return (
        <Modal show={true} onHide={() => props.setModalVisible(false)} title="Edycja zadania" showHideLink={true}>
            <div style={{ padding: 10 }}>
                <BForm data={props.editedData}>
                    {(form) => {
                        return (
                            <>
                                <BText label="Nazwa" {...form("name")} />
                                <BText label="Konfiguracja cron" {...form("name")} />
                                <BText label="Do uruchomienia" {...form("name")} />
                                <a className="btn btn-primary">Zapisz</a>
                                <a className="btn btn-default">Anuluj</a>
                            </>
                        );
                    }}
                </BForm>
            </div>
        </Modal>
    );
};
