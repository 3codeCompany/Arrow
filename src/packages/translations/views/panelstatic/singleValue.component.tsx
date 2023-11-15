import * as React from "react";

import { Table, Column } from "serenity-controls/lib/Table";
import { Comm } from "serenity-controls/lib/lib";
import { IArrowViewComponentProps } from "serenity-controls/lib/backoffice";
import { trans } from "../../front/trans";

interface IProps extends IArrowViewComponentProps {
    language: any;
    originalValue: string;
    country: string
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    public table: any;
    private columns: any;

    constructor(props) {
        super(props);
        this.state = {
            isUploading: false,
            historyModalVisible: false,
        };

        this.columns = [
            Column.id("id", "Id").width(100),
            Column.text("lang", trans("Kod języka")).width(100).className("center"),
            Column.map("lang", trans("Język"), this.props.language).width(100),
            Column.text("value", trans("Wartość"))
                .template((val, row) => {
                    return (
                        <div>
                            {row.loading && (
                                <div>
                                    <i className="fa fa-spinner fa-spin" />
                                </div>
                            )}
                            {row.edited === true && [
                                <textarea
                                    style={{ width: "100%", display: "block" }}
                                    onChange={(e) => (row.changedText = e.target.value)}
                                    defaultValue={val}
                                    autoFocus={true}
                                    onClick={(e) => e.stopPropagation}
                                />,
                                <div>
                                    <a
                                        onClick={this.handleRowChanged.bind(this, row)}
                                        className="btn btn-primary btn-xs btn-block pull-left"
                                        style={{ margin: 0, width: "50%" }}
                                    >
                                        {trans("Zapisz")}
                                    </a>
                                    <a
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            row.edited = false;
                                            row.container.forceUpdate();
                                        }}
                                        className="btn btn-default btn-xs btn-block pull-right"
                                        style={{ margin: 0, width: "50%" }}
                                    >
                                        {trans("Anuluj")}
                                    </a>
                                </div>,
                            ]}
                            {!row.loading && !row.edited && <div>{val}</div>}
                        </div>
                    );
                })
                .set({ styleTemplate: (row) => (row.edited ? { padding: 0 } : {}) })
                .onClick((row, column, rowContainer) => {
                    row.edited = true;
                    row.changedText = row.value;
                    row.container = rowContainer;
                    rowContainer.forceUpdate();
                }),
            Column.text("original", trans("Orginał")).noSorter().noFilter(),
        ];
    }


    public handleRowChanged(row, e) {
        e.stopPropagation();
        row.loading = true;
        row.edited = false;
        row.container.forceUpdate();
        Comm._post(this.props._baseURL + "/inlineUpdate", { key: row.id, newValue: row.changedText }).then(() => {
            this.props._notification("Pomyślnie zmodyfikowano element");
            row.value = row.changedText;
            row.loading = false;
            this.table.load();
        });
    }


    public render() {
        const s = this.state;

        return (
            <div>
                <div className="panel-body-margins">
                    <Table
                        rememberState={true}
                        columns={this.columns}
                        remoteURL={this.props._baseURL + "/asyncSingleValue/" + this.props.originalValue}
                        ref={(table) => (this.table = table)}
                    />
                </div>
            </div>
        );
    }
}
