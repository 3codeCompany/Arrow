import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";
import { Row } from "frontend/src/layout/BootstrapLayout";
import { IArrowViewComponentProps } from "frontend/src/lib/PanelComponentLoader";
import PrintJSON from "frontend/src/utils/PrintJSON";

interface IComponentProps extends IArrowViewComponentProps {
    missMatches: any;
}

interface IState {}

export default class ArrowViewComponent extends React.Component<IComponentProps, IState> {
    constructor(props: IComponentProps) {
        super(props);
        this.state = {};
    }

    public render() {
        const s = this.state;
        const p = this.props;

        return (
            <div>
                <Navbar>
                    <span>Utils</span>
                    <span>ORM Change Index</span>
                </Navbar>
                <div className={"panel-body-margins"}>
                    <PrintJSON json={p.missMatches}/>
                </div>
            </div>
        );
    }
}
