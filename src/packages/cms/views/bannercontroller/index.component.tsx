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
import {FilterPanel} from "../../../../../../../../node_modules_shared/frontend/src/ctrl/filters/FilterPanel";

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
            filterModalVisible: false,
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

                    <button
                        className={"filterBtn"}
                        onClick={() => {
                            this.setState({
                                filterModalVisible: true,
                            });
                        }}
                    >
                        {__("Filtry")}
                    </button>
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
                               Column.bool("active", "Aktywny")
                                   .onClick((row, val) => {
                                       const active = row.active;
                                       Comm._post(this.props._baseURL + `/${row.id}/active`, {active})
                                           .then(() => {
                                               this.props._notification("Sukces", "Zmieniono wartość");
                                               this.table.load();
                                           })
                                   }),
                               Column.text("visibility", "Widoczny").template((val, row) => {
                                   const rtr = (val == "male") ? "mężczyzna" : (val == "female") ? "kobieta" : "uniwersalny";
                                   return <div style={{fontSize: "0.8em", color: "#686868", textTransform: "uppercase"}}>{rtr}</div>;
                               }).className("center"),
                               Column.text("country", "Kraj").template((val) => {
                                   return <div style={{fontSize: "0.8em", color: "#686868", textTransform: "uppercase"}}>{val}</div>
                               }),
                               Column.text("lang", "Język").template((val) => {
                                   return <div style={{fontSize: "0.8em", color: "#3e3e3e", textTransform: "uppercase"}}>{val}</div>
                               }),
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


                <Modal
                    show={this.state.filterModalVisible}
                    title={__("Filtry")}
                    animate={true}
                    animation={"fadeInDown"}
                    right={25}
                    top={50}
                    onHide={() => {
                        this.setState({filterModalVisible: false});
                    }}
                >
                    <FilterPanel
                        filters={this.state.filters}
                        onChange={(filter, filters) => {
                            this.setState({filters: {...this.state.filters, ...filters}});
                        }}
                        items={[
                            FilterHelper.text("file_name", __("Nazwa pliku"), false),
                        ]}
                    />
                </Modal>

            </div>
        );
    }
}
