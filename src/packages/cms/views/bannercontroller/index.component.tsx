import * as React from "react";
import { Navbar } from "serenity-controls/lib/Navbar";
import {Column, Table} from "serenity-controls/lib/Table";

import {BForm, BSelect, BSwitch, BText, BTextarea, BWysiwig} from "serenity-controls/lib/BForm";
import {Comm} from "serenity-controls/lib/lib";

import {IArrowViewComponentProps} from "serenity-controls/lib/backoffice";
import {CommandBar} from "serenity-controls/lib/CommandBar";
import { CommonIcons } from "serenity-controls/lib/lib/CommonIcons";
import {FilterHelper} from "serenity-controls/lib/filters";
import {FilterPanel} from "serenity-controls/lib/filters";
import {Modal} from "serenity-controls/lib/Modal";
import { trans } from "../../../translations/front/trans";

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
                    <span>{trans("Sklep")}</span>
                    <span>{trans("Bannery")}</span>

                    <button
                        className={"filterBtn"}
                        onClick={() => {
                            this.setState({
                                filterModalVisible: true,
                            });
                        }}
                    >
                        {trans("Filtry")}
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
                               Column.text("sort", "Sort").template((val, row) => {
                                   return (
                                       <input
                                           style={{
                                               padding: "3px 7px",
                                               width: 50,
                                               textAlign: "center",
                                               border: "1px solid lightgrey",
                                           }}
                                           type="text"
                                           defaultValue={val}
                                           onBlur={(e) => {
                                               if (e.target.value !== val) {
                                                   Comm._post(this.props._baseURL + `/sortUpdate`, {banner: row.id, sort: e.target.value}).then(() => {
                                                       this.props._notification("Sukces", "Zmieniono wartość.");
                                                       this.table.load();
                                                   });
                                               }
                                           }}
                                       />
                                   )
                               }).className("center").width(100),
                               Column.template("", () => {
                                   return (
                                       <CommonIcons.chevronUp />
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
                                       <CommonIcons.chevronDown />
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
                                       <CommonIcons.edit />
                                   )
                               }).className("center")
                                   .onClick((row, val) => {
                                   this.props._goto(this.props._baseURL + `/${row.id}/edit`);
                               }),
                               Column.template("Usuń",() => {
                                   return (
                                       <CommonIcons.delete />
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
                    title={trans("Filtry")}
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
                            FilterHelper.text("file_name", trans("Nazwa pliku"), false),
                        ]}
                    />
                </Modal>

            </div>
        );
    }
}
