import * as React from "react";

import { Navbar } from "serenity-controls/lib/Navbar";
import { Row } from "serenity-controls/lib/Row";
import { IArrowViewComponentProps } from "serenity-controls/lib/backoffice";
import { PrintJSON } from "serenity-controls/lib/PrintJSON";

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
