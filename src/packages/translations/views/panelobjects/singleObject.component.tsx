import * as React from "react";

import { Column, IDataQuery, ITableDataInput, Table, ColumnHelper } from "serenity-controls/lib/Table";

import { Comm } from "serenity-controls/lib/lib";
import { IArrowViewComponentProps } from "serenity-controls/lib/backoffice";
import { CommandBar } from "serenity-controls/lib/CommandBar";
import { PrintJSON } from "serenity-controls/lib/PrintJSON";
import { trans } from "../../front/trans";

interface IProps extends IArrowViewComponentProps {
    data: any;
    model: any;
    objectKey: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    private table: any;
    private changedData: any = {};
    public styleTemplate: any;

    constructor(props: IProps) {
        super(props);
        this.state = {
            changedData: {},
        };

        this.styleTemplate = (row) => {
            if (row.langCode.toLowerCase() == "pl") {
                return {
                    backgroundColor: "grey",
                    color: "white",
                };
            }
        };
    }

    public handleSave = () => {
        Comm._post(this.props._baseURL + `/single-object-save/${encodeURI(this.props.model)}/${this.props.objectKey}`, {
            data: this.state.changedData,
        }).then(() => {
            this.props._notification(trans("Zapisano tłumaczenia"));
        });
    };

    public render() {
        const data = this.props.data;

        return (
            <>
                <CommandBar
                    items={[
                        {
                            key: "f1",
                            label: trans("Zapisz"),
                            icon: "Save",
                            onClick: this.handleSave,
                        },
                    ]}
                />
                <Table
                    dataProvider={(input: IDataQuery): Promise<ITableDataInput> => {
                        return Promise.resolve({
                            data: this.props.data,
                            countAll: this.props.data.length,
                            debug: "",
                        });
                    }}
                    columns={[ColumnHelper.text("langCode", "Kod"), ColumnHelper.text("lang", "Język")].concat(
                        Object.entries(this.props.data[0].trans).map(([key, name]) => {
                            return ColumnHelper.template(key, (val, row) => {
                                if (row.langCode == "Pl") {
                                    return <span style={{ fontSize: 16 }}>{row.trans[key]}</span>;
                                } else {
                                    return (
                                        <input
                                            type={"text"}
                                            defaultValue={"" + row.trans[key]}
                                            onChange={(ev) => {
                                                ev.preventDefault();
                                                ev.stopPropagation();
                                                const tmp = this.state.changedData;
                                                if (tmp[row.langCode] == undefined) {
                                                    tmp[row.langCode] = {};
                                                }
                                                tmp[row.langCode][key] = ev.target.value;
                                                this.setState({ changedData: tmp });
                                            }}
                                        />
                                    );
                                }
                            }).styleTemplate(this.styleTemplate);
                        }),
                    )}
                    onPage={100}
                    showFooter={false}
                    showHeader={false}
                />
            </>
        );
    }
}
