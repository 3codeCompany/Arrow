declare var EventSource: any;
import Navbar from "frontend/src/ctrl/Navbar";
import Panel from "frontend/src/ctrl/Panel";

import * as React from "react";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {BForm, BSwitch, BContainer} from "frontend/src/layout/BootstrapForm";
import {CommandBar} from "frontend/src/ctrl/CommandBar";
import Comm from "frontend/src/lib/Comm";
import {Row} from "frontend/src/layout/BootstrapLayout";
import {Copyable} from "frontend/src/ctrl/Copyable";
import Icon from "frontend/src/ctrl/Icon";


interface IProps extends IArrowViewComponentProps {
    ARROW_DEV_MODE: string,
    routes: any
}

export default class ArrowViewComponent extends React.Component<IProps, any> {

    handleCacheRemove() {

        Comm._post(this.props.baseURL + "/cache/remove").then(() => {
            this.props._notification("Cache", "Deleted");
        })

    }
    handleDevStateChange(){
        Comm._post(this.props.baseURL + "/changeDevState").then(() => {
            this.props._notification("Dev state", "Changed");
            this.props._reloadProps();
        })
    }

    public render() {
        let {ARROW_DEV_MODE, routes} = this.props;
        return (
            <div>
                <CommandBar
                    isSearchBoxVisible={false}
                    items={[
                        {key: "f1", label: "Usuń cache", icon: "Delete", onClick: this.handleCacheRemove.bind(this)},
                    ]}
                />

                <Navbar>
                    <span>Utils</span>
                    <span>Developer Tools</span>
                </Navbar>
                <div>
                    <Panel>
                        <BForm editable={false}>
                            {() => <>
                                <Row md={[2, 3]} noGutters={false}>
                                    <BSwitch label={"ARROW_DEV_MODE"} options={{false: "No", true: "Yes"}} value={ARROW_DEV_MODE} editable={false}/>
                                    <BContainer label={" "}>
                                        <a className={"btn "} onClick={this.handleDevStateChange.bind(this)}><Icon name={"Switch"} /> Switch</a>
                                    </BContainer>
                                </Row>
                            </>}
                        </BForm>
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
                                {Object.entries(routes).map(([path, data]) => <tr key={path}>
                                    <td>{path}</td>
                                    <td>{data._controller}</td>
                                    <td>{data._method}</td>
                                    <td>{data._debug.line}</td>
                                    <td style={{textAlign: "center"}}>
                                        <Copyable
                                            toCopy={data._debug.file + ":" + data._debug.line}>
                                        </Copyable>
                                    </td>
                                </tr>)}
                                </tbody>
                            </table>
                        </div>

                    </Panel>
                </div>
            </div>
        );
    }
}
