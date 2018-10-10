import { Navbar } from "frontend/lib/Navbar";
import { Panel } from "frontend/lib/Panel";

import * as React from "react";
import { IArrowViewComponentProps } from "frontend/lib/backoffice";
import { BForm, BSwitch, FieldPlaceholder } from "frontend/lib/BForm";
import { CommandBar } from "frontend/lib/CommandBar";
import {Comm} from "frontend/lib/lib";
import { Row } from "frontend/lib/Row";
import { Copyable } from "frontend/lib/Copyable";
import { Icon } from "frontend/lib/Icon";
import * as path from "path";

interface IProps extends IArrowViewComponentProps {
    ARROW_DEV_MODE: string;
    routes: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    public handleCacheRemove = () => {
        Comm._post(this.props._baseURL + "/cache/remove").then(() => {
            this.props._notification("Cache", "Deleted");
        });
    };
    public handleRoutingCacheRemove = () => {
        Comm._post(this.props._baseURL + "/cache/remove/routing").then(() => {
            this.props._notification("Cache", "Routing delete");
        });
    };

    public render() {
        const { ARROW_DEV_MODE, routes } = this.props;
        return (
            <div>
                <CommandBar
                    isSearchBoxVisible={false}
                    items={[
                        { key: "f1", label: "Usuń cache", icon: "Delete", onClick: this.handleCacheRemove },
                        { key: "f2", label: "Usuń cache routingu", icon: "Delete", onClick: this.handleRoutingCacheRemove },
                    ]}
                />

                <Navbar>
                    <span>Utils</span>
                    <span>Developer Tools</span>
                </Navbar>
                <div className={"panel-body-margins"}>
                    <div className="w-table">
                        <table className={""}>
                            <thead>
                                <tr>
                                    <th>Path</th>
                                    <th>Controller</th>
                                    <th>Method</th>
                                    <th>Line</th>
                                    <th>Copy</th>
                                </tr>
                            </thead>
                            <tbody>
                                {Object.entries(routes).map(([filePath, data]: any) => (
                                    <tr key={filePath + data._debug.line}>
                                        <td>
                                            <a href={filePath} target={"blank"}>
                                                {filePath}
                                            </a>
                                        </td>
                                        <td>{data._controller}</td>
                                        <td>{data._method}</td>
                                        <td>{data._debug.line}</td>
                                        <td style={{ textAlign: "center" }}>
                                            <Copyable toCopy={data._debug.file + ":" + data._debug.line} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        );
    }
}
