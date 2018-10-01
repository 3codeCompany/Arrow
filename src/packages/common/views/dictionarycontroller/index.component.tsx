import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import { Row } from "frontend/src/layout/BootstrapLayout";
import { IArrowViewComponentProps } from "frontend/src/lib/PanelComponentLoader";
import {BForm, BText, BTextarea} from "frontend/src/layout/BootstrapForm";

import { Column, Table } from "../../../../../../../../node_modules_shared/frontend/src/ctrl/Table/Table";
import { CommandBar } from "../../../../../../../../node_modules_shared/frontend/src/ctrl/CommandBar";
import Icon from "../../../../../../../../node_modules_shared/frontend/src/ctrl/Icon";
import Comm from "../../../../../../../../node_modules_shared/frontend/src/lib/Comm";
import {Modal} from "frontend/src/ctrl/overlays/Modal";
import {confirmDialog} from "frontend/src/ctrl/overlays/ConfirmDialog";

interface IComponentProps extends IArrowViewComponentProps {
    parent: any;
    children: any[];
}

interface IState {
    add: boolean;
    edited: object;
}

export default class ArrowViewComponent extends React.Component<IComponentProps, IState> {
    list: Table;

    constructor(props: IComponentProps) {
        super(props);
        this.state = {
            add: false,
            edited: null
        };
    }

    handleDelete = (row: any) => {
        confirmDialog("Czy na pewno chcesz usunąć: " + `'${row.label}' ?`).then(() => {
            Comm._get(this.props._baseURL + "/delete/" + row.id).then(() => {
                this.props._notification("Usunięto");
                this.list.load();
            });
        });
    };

    public render() {
        const s = this.state;
        const p = this.props;

        return (
            <div>
                <CommandBar
                    items={[
                        {
                            key: "f1",
                            label: "Dodaj",
                            icon: "Add",
                            onClick: () => this.setState({ add: true })
                        }
                    ]}
                />
                <Navbar>
                    <span>System</span>
                    <a onClick={() => this.props._goto(this.props._baseURL + "/list/1")}>Słowniki</a>
                    {this.props.ancestors.length > 1
                        ? this.props.ancestors.filter((el) => el.id != 1).map((el) => (
                              <a
                                  onClick={() => {
                                      this.props._goto(this.props._baseURL + "/list/" + el.id);
                                  }}
                              >
                                  {el.label}
                              </a>
                          ))
                        : null}
                    {this.props.parent.id != 1 && <span>{this.props.parent.label}</span>}
                </Navbar>

                <div className={"panel-body-margins"}>
                    <Table
                        ref={(el) => (this.list = el)}
                        remoteURL={this.props._baseURL + "/listData/" + p.parent.id}
                        columns={[
                            Column.hidden("system_name"),
                            Column.id("id", "Id").width(80),
                            Column.text("label", "Nazwa")
                                .onClick((row) => {
                                    this.props._goto(this.props._baseURL + "/list/" + row.id);
                                })
                                .template((val, row) => {
                                    return (
                                        <>
                                            <div className="pull-right">
                                                <Icon name={"ChromeBackMirrored"} />
                                            </div>
                                            {row.system_name ? (
                                                <span>
                                                    {row.label}
                                                    <br />
                                                    <small style={{ color: "darkgrey" }}>{row.system_name}</small>
                                                </span>
                                            ) : (
                                                val
                                            )}
                                        </>
                                    );
                                }),
                            Column.text("value", "Wartość"),
                            Column.text("data", "Dane").template((val) => <pre style={{whiteSpace: "pre-wrap"}}>{val}</pre>),
                            Column.template("", () => <Icon name={"Edit"} />)
                                .className("center")
                                .width(40)
                                .onClick((row) => this.setState({ add: true, edited: row })),
                            Column.template("", () => <Icon name={"Delete"} />)
                                .className("center darkred")
                                .width(40)
                                .onClick(this.handleDelete)
                        ]}
                    />

                    <Row />
                </div>

                <Modal
                    show={s.add}
                    title={"Dodaj wartość do " + p.parent.label}
                    showHideLink={true}
                    onHide={() => this.setState({ add: false, edited: null })}
                >
                    <div style={{ padding: 10, width: 500 }}>
                        <BForm
                            action={this.props._baseURL + (s.edited !== null ? "/save/" : "/create/") + p.parent.id}
                            namespace={"data"}
                            data={this.state.edited ? { ...this.state.edited } : {}}
                            onSuccess={() => {
                                this.props._notification(s.edited ? "Zapisano zmiany" : "Dodano");
                                this.setState({ add: false, edited: null });
                                this.list.load();
                            }}
                        >
                            {(form) => {
                                return (
                                    <>
                                        <BText label={"Etykieta"} {...form("label")} autoFocus={true} />
                                        <BText label={"Wartosc"} {...form("value")} />
                                        <BTextarea label={"Dane dodatkowe"} {...form("data")} style={{height: 100}} />
                                        <BText
                                            label={"Nazwa systemowa"}
                                            {...form("system_name")}
                                            help={"Modyfikuj tylko jeśli wiesz czo robisz!"}
                                        />
                                        <button className="pull-right btn btn-primary">
                                            {s.edited ? "Zmień" : "Dodaj"}
                                        </button>
                                    </>
                                );
                            }}
                        </BForm>
                    </div>
                </Modal>
            </div>
        );
    }
}
