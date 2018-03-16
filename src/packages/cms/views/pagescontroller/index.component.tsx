import * as React from "react";

import Navbar from "frontend/src/ctrl/Navbar";

import {Column, Table} from "frontend/src/ctrl/Table";
import {confirm} from "frontend/src/ctrl/Overlays";
import {IArrowViewComponentProps} from "frontend/src/lib/PanelComponentLoader";
import Icon from "frontend/src/ctrl/Icon";
import Comm from "frontend/src/lib/Comm";
import {CommandBar} from "frontend/src/ctrl/CommandBar";

interface IProps extends IArrowViewComponentProps {
    language: any;
    search: string;
    langToDownload: any;
}

export default class ArrowViewComponent extends React.Component<IProps, any> {
    public table: any;

    constructor(props) {
        super(props);
        this.state = {
            langToDownload: false,
            search: "",
            isUploading: false,
        };

    }

    public render() {
        const s = this.state;
        return (
            <div>
                <CommandBar items={[
                    {
                        key: "al", label: "Dodaj kategorie", icon: "Add", onClick: () => {
                            this.props._goto(this.props._baseURL + "/create");
                        },
                    },
                ]}/>
                <Navbar>
                    <span>{__("Sklep")}</span>
                    <span>{__("Kategorie")}</span>
                </Navbar>

                <div className={"panel-body-margins"}>
                    <Table remoteURL={this.props._baseURL + "/asyncIndex"}
                           ref={(el) => this.table = el}
                           columns={[
                               Column.hidden("id", "Id"),
                               Column.hidden("depth", "depth"),
                               Column.hidden("seo_title", "seo_title"),
                               Column.hidden("seo_keywords", "seo_keywords"),
                               Column.hidden("seo_description", "seo_description"),
                               Column.hidden("seo_page_text", "seo_page_text"),

                               Column.bool("active", "Active")
                                   .width("80px")
                                   .className("center")
                                   .onClick((row) => {
                                       const data = row.active;

                                       Comm._post(this.props._baseURL + `/${row.id}/toggleActive`, {
                                           active: row.active,
                                           row: row.id
                                       })
                                           .then(() => {
                                               const activate = data == 1 ? "Deaktywowano" : "Aktywowano";
                                               if (data == 1) {
                                                   this.props._notification("Zmiana", activate, {level: "error"});
                                               } else {
                                                   this.props._notification("Zmiana", activate);
                                               }

                                               this.table.load();
                                           });
                                   }),
                               Column.text("available", "Dostępny")
                                   .template((val, row) => {
                                       const returnVal = val == 1 ? "Tak" : "Nie";

                                       return returnVal;
                                   })
                                   .width(100)
                                   .className("center boldSmall"),
                               Column.text("name", "Nazwa").template( (val, row) => {
                                   if(row.depth == 0 || row.depth == 1){
                                       const showicon = <Icon name={"FabricNewFolder"}/>;
                                   } else if(row.depth == 2){
                                       const showicon = <Icon name={"ChevronRight"}/>;
                                   } else if(row.depth == 3){
                                       const showicon = <Icon name={"DoubleChevronRight"}/>;
                                   };
                                   return <span>{showicon} {row.name}</span>;
                               } ),

                               Column.template("", (val, row) => <Icon name={"ChevronUp"}/>)
                                   .className("center")
                                   .width("20px")
                                   .onClick((row) => {
                                       Comm._post(this.props._baseURL + `/${row["id"]}/moveUp`, {key: row.id}).then(() => {
                                           this.props._notification("Zmiana", "Przeniesiono w góre");
                                           this.table.load();
                                       });
                                   }),
                               Column.template("", (val, row) => <Icon name={"ChevronDown"}/>)
                                   .className("center")
                                   .width("20px")
                                   .onClick((row) => {
                                       Comm._post(this.props._baseURL + `/${row["id"]}/moveDown`, {key: row.id}).then(() => {
                                           this.props._notification("Zmiana", "Przeniesiono w dół");
                                           this.table.load();
                                       });
                                   }),
                               Column.template("", (val, row) => <Icon name={"CalculatorAddition"}/>)
                                   .className("center")
                                   .width("20px")
                                   .onClick((row) => {
                                       this.props._goto(this.props._baseURL + `/${row.id}/edit`);
                                   }),

                               Column.text("id", "SEO").template((val, row) => {
                                   if (row.seo_title == null) {
                                       row.seo_title = "";
                                   }
                                   if (row.seo_keywords == null) {
                                       row.seo_keywords = "";
                                   }
                                   if (row.seo_description == null) {
                                       row.seo_description = "";
                                   }
                                   if (row.seo_page_text == null) {
                                       row.seo_page_text = "";
                                   }
                                   const seoTitle = row.seo_title.length > 1 ? "" : "tytuł, ";
                                   const seoKeywords = row.seo_keywords.length > 1 ? "" : "słowa kluczowe, ";
                                   const seoDesc = row.seo_description.length > 1 ? "" : "opis seo, ";
                                   const seoPageText = row.seo_page_text.length > 1 ? "" : "tekst pod produktami";

                                   if (seoTitle.length > 1 || seoKeywords.length > 1 || seoDesc.length > 1 || seoPageText.length > 1) {
                                       const spanIcon = <Icon name={"Error"}/>;
                                       const spanText = "Uzupełnij pola:";
                                   } else {
                                       const spanIcon = "";
                                       const spanText = "";
                                   }

                                   return <span
                                       className={"redAccent"}>{spanIcon} {spanText} {seoTitle} {seoKeywords} {seoDesc} {seoPageText}</span>;
                               }),
                               Column.text("link", "Link"),
                               Column.text("google_cat", "Kategoria Google"),

                               Column.template("Edytuj", (val, row) => <Icon name={"Edit"}/>)
                                   .className("center")
                                   .width("60px")
                                   .onClick((row) => {
                                       this.props._goto(this.props._baseURL + `/${row.id}/edit`);
                                   }),
                           ]}

                    />
                </div>
            </div>
        );
    }
}
