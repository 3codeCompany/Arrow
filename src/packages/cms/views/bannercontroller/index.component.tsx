import * as React from "react";
import Navbar from "frontend/src/ctrl/Navbar";
import {Column, Table} from "frontend/src/ctrl/Table";

import {confirm, Modal} from "frontend/src/ctrl/Overlays";
import {BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from "frontend/src/layout/BootstrapForm";
import Comm from "frontend/src/lib/Comm";
import {Row} from "frontend/src/layout/BootstrapLayout";

import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import {CommandBar} from "frontend/src/ctrl/CommandBar";
import {Datasource} from "frontend/src/lib/Datasource";
import {LoaderContainer} from "frontend/src/ctrl/LoaderContainer";
import Icon from "frontend/src/ctrl/Icon";
import {FilterHelper} from "frontend/src/ctrl/filters/FilterHelper";
import download from "../../../../../../../../node_modules_shared/frontend/src/lib/Downloader";

interface IProps extends IArrowViewComponentProps {
    groups: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    private columns: Column[];
    private table: Table;

    constructor(props) {
        super(props);
        this.state = {
            currEdited: false,
            dataLoading: false,
            currEditedData: {},
            filters: null,
        };


    }

    _prepareAvaiablePlaces(){
        let avaiablePlaces = [];

        Object.values(this.props.places).map((el) => {
            avaiablePlaces.push({
                key: el,
                label: el,
                icon: "ChromeMinimize",
                onClick: () => {
                    this.setState({
                        filters: {place: {
                                caption: undefined,
                                condition: "==",
                                field: "place",
                                label: el,
                                labelCaptionSeparator: ":",
                                value: el,
                            }}
                    })
                }
            })
        });

        return avaiablePlaces;
    }

    public render() {
        const s = this.state;

        return (
            <div>
                <CommandBar items={[
                    {key: "al", label: "Dodaj banner", icon: "Add", onClick: () => {
                            this.props._goto(this.props._baseURL + "/create");
                        }},
                ]}/>
                <Navbar>
                    <span>{__("Sklep")}</span>
                    <span>{__("Bannery")}</span>
                </Navbar>

                <div className={"panel-body-margins"}>
                    <Table remoteURL={this.props._baseURL + `/asyncIndex`}
                           ref={(el) => this.table = el}
                           filters={this.state.filters}
                           rememberState={true}
                           onFiltersChange={(filters) => this.setState({ filters })}
                           columns={[
                               Column.hidden("id"),
                               Column.text("place", "Miejsce")
                                   .template((val, row) => {
                                       return (
                                           <div>{this.props.places[val]}</div>
                                       );
                                   }).className("center").width(180)
                                   .noFilter()
                                   .addFilter(FilterHelper.select("place", "Miejsce baneru", this._prepareAvaiablePlaces(), true).get())
                               ,
                               Column.bool("active", "Aktywny"),
                               Column.text("country", "Kraj"),
                               Column.text("lang", "Język"),
                               Column.text("title", "Tytuł"),
                               Column.template("", () => {
                                   return (
                                       <Icon name={"ChevronUp"}/>
                                   )
                               }).className("center")
                                   .onClick((row, val) => {
                                       Comm._get(this.props._baseURL + `/${row.id}/moveUp`)
                                           .then((response) => {
                                               this.props._notification("Sukces", "Przeniesiono w górę");
                                               this.table.load();
                                           })
                                   }),
                               Column.template("", () => {
                                   return (
                                       <Icon name={"ChevronDown"}/>
                                   )
                               }).className("center")
                                   .onClick((row, val) => {
                                       Comm._get(this.props._baseURL + `/${row.id}/moveDown`)
                                           .then((response) => {
                                               this.props._notification("Sukces", "Przeniesiono w dół");
                                               this.table.load();
                                           })
                                   }),
                               Column.template("Edytuj",() => {
                                   return (
                                       <Icon name={"edit"}/>
                                   )
                               }).className("center")
                                   .onClick((row, val) => {
                                   this.props._goto(this.props._baseURL + `/${row.id}/edit`);
                               }),
                               Column.template("Usuń",() => {
                                   return (
                                       <Icon name={"delete"}/>
                                   )
                               }).className("center darkred")
                                   .onClick((row, val) => {
                                       Comm._get(this.props._baseURL + `/${row.id}/delete`)
                                           .then((resp) => {
                                               this.props._notification("Sukces", "Usunięto pomyślnie");
                                               this.table.load();
                                           })
                                   }),
                           ]}
                    />
                </div>

            </div>
        );
    }
}
