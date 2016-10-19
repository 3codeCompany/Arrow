function fitFont(cont, minSize){

    var text = $(">span", cont);
    if(text.length == 0){
        var contents = cont.html();
        cont.html("");
        var text = $("<span />")
            .css('display', 'inline-block')
            .html(contents)
            .appendTo(cont);
    }

    var currSize = parseInt(text.css("font-size"));
    console.log(currSize);
    if(typeof(minSize) != "undefined" && minSize >= currSize - 1){
        cont.css({
            'overflow':'hidden',
            'text-overflow':'ellipsis'
        });
        return;

    }

    if( text.width() > cont.width() || text.height() > cont.height() ){
        text.css( "font-size",  currSize -1 );
        return fitFont(cont, minSize)
    }else{
        text.css('margin-top', (cont.height() - text.height()) /2 );
    }

}

var ctrlFormCallbacks = {

    beforeSerialize: function (form) {
        el = $("[type='submit']", form);

        if (form.parent().is(".modal-body")) {
            el = form.parent().next().find(".btn-primary");
        }
        var text = el.text();
        el.html(text + '<i class="fa fa-spinner submit-indicator fa-spin"></i> ').attr('disabled', 'disabled');

    },
    clearCommunicates: function (form) {
        form.find(".form-field-error-text", form).remove();
        form.find(".form-error-text", form).parents(".control-group:eq(0)").remove();
        form.find(".error").removeClass("error");
        var indicator = form.find(".submit-indicator");
        indicator.parent().removeAttr("disabled");
        indicator.remove()

        if (form.parent().is(".modal-body")) {
            var indicator = form.parent().next().find(".submit-indicator");
            indicator.parent().removeAttr("disabled");
            indicator.remove()
        }
    },
    fieldError: function (input, errors, form) {

        if(input.length == 0 ){
            console.log(errors);
            ctrlFormCallbacks.formError(form,errors)
            return;
        }

        var str = "";
        for (var i = 0; i < errors.length; i++) {
            str += (i == 0 ? "" : ", ") + errors[i];
        }
        var ctrls = input.parents(".controls")

        ctrls.parent().addClass("error");
        var error = $('<div class="form-field-error-text"><i class="fa fa-exclamation-triangle"></i> ' + str + '</div>');
        //in no bootstrap form
        if (ctrls.length == 0 || ctrls.is(".form-inline")) {
            //error.addClass("alert-error").css({padding: '4px'});

            if (input.parent().is(".input-append") || input.parent().is(".input-prepend")) {
                error.insertAfter(input.parent());

            } else {
                error.insertAfter(input);
                error.css({'left': input.width() + 40, 'white-space': 'nowrap'})
                error.mouseleave(function(){ error.fadeOut() })
                input.focus(function(){ error.fadeOut() });
            }
        } else {

            //
            if (input.parent().is(".input-append") || input.parent().is(".input-prepend")) {
                error.insertAfter(input.parent());
            } else if (input.parent().is(".btn")) {
                error.insertAfter(input.parent());
            } else {
                error.insertAfter(input);
            }
        }
    },
    formError: function (form, errors) {
        form.find(">.alert").remove()
        if ( errors && errors.length)
            form.prepend($('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> ' + errors.join(",") + '</div>'));
    },
    success: function (successCommunicate, response, form) {
        form.find(">.alert").remove()
        if (successCommunicate.length) {
            text = successCommunicate.html();
            text = text.replace(/\{(.+?)\}/g, function (match, contents, offset, s) {
                    return response[contents];
                }
            );
            successCommunicate.html(text);
            successCommunicate.show().removeClass("hidden");
            form.hide();
            if (form.parent().is(".modal-body")) {
                form.parent().next().find(".btn-primary").hide();
            }
        }
    }
}




var popupOnSuccess = function (response, form) {
    var normalBehavior = function (win) {
        if (true) {
            try {
                parent.Page.reloadHandler(true);
            } catch (e) {
                alert(e);
            }

        } else {
            win.Page.reloadHandler(true);
        }
    }
    if (Page.afterSubmitCallback != null) {
        Page.afterSubmitCallback(normalBehavior, response);
    } else {

        normalBehavior(window.parent);
    }
    return false;
};

var popupOnError = function (form) {
    $("a", ".top-actions").show();
    return true;
}

var parsePopup = function (context) {

    Serenity.scanForWidgets(context);
    $(".lang-translate", context).click(function (e) {
        e.preventDefault();
        var val = $(this).attr("data-value");
        var from = "pl";
        var to = $(this).attr("data-to");
        var link = $(this);
        var url = "http://3code.pl/services/translate.php";
        var i = link.find("i");
        i.toggleClass("icon-spin").toggleClass("icon-spinner");
        $.ajax({
            url: url,
            data: {to: to, text: val, from: "pl"},
            success: function (data) {
                link.prev().val(data)
                i.toggleClass("icon-spin").toggleClass("icon-spinner");
            },
            dataType: "jsonp",
            jsonp: "jsonpCallback",
            jsonpCallback: "jsonpCallback"
        });
    });

    $(".lang-translate-large", context).click(function (e) {
        e.preventDefault();
        var from = "pl";
        var to = $(this).attr("data-to");
        var tempTranslationVal = $(this).next().next().val();
        //alert(tempTranslationVal);
        $.post('o:common::/translations/saveTmpText', {"text": tempTranslationVal}, function (response) {
            var address = 'http://<?=$_SERVER["HTTP_HOST"]?>/v:common::/translations/openTempValue?file=' + response.result;
            var win = window.open('http://translate.google.pl/translate?hl=pl&sl=pl&tl=' + to + '&u=' + address, '_blank');
            win.focus();
        }, 'json');

    });


    if ($(".popup-window-menu a", context).length == 0) {
        var link = $('<a class="cancel" href="#">Zamknij</a>');
        $(".popup-window-menu h3").after(link);
    }

    var editProtetion = $(".edit-protection", context);

    function applyEditProtection() {
        $("input[type='text']").attr("disabled", true).attr('style', 'background-color: rgb(220,220,220) !important');
        $(".save").hide();
        $(".edit-protection").show();
    }

    $(".top-actions a", context).each(function () {
        var text = $(this).text();

        if ($(this).is(".cancel") || text == "Zakończ" || text == "anuluj" || text == "Anuluj" || text == "Wróć" || text == "Zamknij" || text == "Close"|| text == "Cancel") {

            $(this).click(function () {
                Page.closeLastDialog();

                return false;
            });
        }
        if ($(this).is(".save")) {
            $(this).click(function () {
                var link = $(this);
                var parent = link.parent();
                $("a", parent).hide();

                if ($(this).is(".exit")) {
                    Page.afterSubmitCallback = function (normalBehavior) {
                        normalBehavior();
                        Page.closeLastDialog();
                    }
                } else {
                    Page.afterSubmitCallback = function (normalBehavior, response) {

                        Page.reloadDialog(parent);
                        $("a", parent).show();
                        $(".edit-protection").hide();
                        $(".loading", parent).hide();
                        if (editProtetion.length > 0)
                            applyEditProtection();
                        normalBehavior();
                        window.location.href = updateQueryStringParameter(window.location.href, "id", response.key);
                    }
                }
                $("form", context).submit();
                return false;
            });
        }


    });


    if (editProtetion.length > 0) {
        applyEditProtection();
        editProtetion.click(function (e) {
            $("input[type='text']").attr("disabled", false).removeAttr("style");
            e.preventDefault();
            $(".save", context).show();
            $(this).hide();
        });
    }

    $(".switch-lang", context).change(function () {
        if($(".admin-dialog-window").length > 0){
            Page.reloadDialog($(".admin-dialog-window").last().children(), updateQueryStringParameter($(this).attr('data-url'), "currLang", $(this).val()));
        }else{

            window.location.href = updateQueryStringParameter($(this).attr('data-url'), "currLang", $(this).val());
        }
    });


};





/**
 * Bootstrap.js by @mdo and @fat, extended by @ArnoldDaniels.
 * plugins: bootstrap-fileupload.js
 * Copyright 2012 Twitter, Inc.
 * http://www.apache.org/licenses/LICENSE-2.0.txt
 */
!function (e) {
    var t = function (t, n) {
        this.$element = e(t), this.type = this.$element.data("uploadtype") || (this.$element.find(".thumbnail").length > 0 ? "image" : "file"), this.$input = this.$element.find(":file");
        if (this.$input.length === 0)return;
        this.name = this.$input.attr("name") || n.name, this.$hidden = this.$element.find('input[type=hidden][name="' + this.name + '"]'), this.$hidden.length === 0 && (this.$hidden = e('<input type="hidden" />'), this.$element.prepend(this.$hidden)), this.$preview = this.$element.find(".fileupload-preview");
        var r = this.$preview.css("height");
        this.$preview.css("display") != "inline" && r != "0px" && r != "none" && this.$preview.css("line-height", r), this.original = {exists: this.$element.hasClass("fileupload-exists"), preview: this.$preview.html(), hiddenVal: this.$hidden.val()}, this.$remove = this.$element.find('[data-dismiss="fileupload"]'), this.$element.find('[data-trigger="fileupload"]').on("click.fileupload", e.proxy(this.trigger, this)), this.listen()
    };
    t.prototype = {listen: function () {
        this.$input.on("change.fileupload", e.proxy(this.change, this)), e(this.$input[0].form).on("reset.fileupload", e.proxy(this.reset, this)), this.$remove && this.$remove.on("click.fileupload", e.proxy(this.clear, this))
    }, change: function (e, t) {
        if (t === "clear")return;
        var n = e.target.files !== undefined ? e.target.files[0] : e.target.value ? {name: e.target.value.replace(/^.+\\/, "")} : null;
        if (!n) {
            this.clear();
            return
        }
        this.$hidden.val(""), this.$hidden.attr("name", ""), this.$input.attr("name", this.name);
        if (this.type === "image" && this.$preview.length > 0 && (typeof n.type != "undefined" ? n.type.match("image.*") : n.name.match(/\.(gif|png|jpe?g)$/i)) && typeof FileReader != "undefined") {
            var r = new FileReader, i = this.$preview, s = this.$element;
            r.onload = function (e) {
                i.html('<img src="' + e.target.result + '" ' + (i.css("max-height") != "none" ? 'style="max-height: ' + i.css("max-height") + ';"' : "") + " />"), s.addClass("fileupload-exists").removeClass("fileupload-new")
            }, r.readAsDataURL(n)
        } else this.$preview.text(n.name), this.$element.addClass("fileupload-exists").removeClass("fileupload-new")
    }, clear: function (e) {
        this.$hidden.val(""), this.$hidden.attr("name", this.name), this.$input.attr("name", "");
        if (navigator.userAgent.match(/msie/i)) {
            var t = this.$input.clone(!0);
            this.$input.after(t), this.$input.remove(), this.$input = t
        } else this.$input.val("");
        this.$preview.html(""), this.$element.addClass("fileupload-new").removeClass("fileupload-exists"), e && (this.$input.trigger("change", ["clear"]), e.preventDefault())
    }, reset: function (e) {
        this.clear(), this.$hidden.val(this.original.hiddenVal), this.$preview.html(this.original.preview), this.original.exists ? this.$element.addClass("fileupload-exists").removeClass("fileupload-new") : this.$element.addClass("fileupload-new").removeClass("fileupload-exists")
    }, trigger: function (e) {
        this.$input.trigger("click"), e.preventDefault()
    }}, e.fn.fileupload = function (n) {
        return this.each(function () {
            var r = e(this), i = r.data("fileupload");
            i || r.data("fileupload", i = new t(this, n)), typeof n == "string" && i[n]()
        })
    }, e.fn.fileupload.Constructor = t, e(document).on("click.fileupload.data-api", '[data-provides="fileupload"]', function (t) {
        var n = e(this);
        if (n.data("fileupload"))return;
        n.fileupload(n.data());
        var r = e(t.target).closest('[data-dismiss="fileupload"],[data-trigger="fileupload"]');
        r.length > 0 && (r.trigger("click.fileupload"), t.preventDefault())
    })
}(window.jQuery)


function updateQueryStringParameter(uri, key, value) {
    var re = new RegExp("([?|&])" + key + "=.*?(&|$)", "i");
    separator = uri.indexOf('?') !== -1 ? "&" : "?";
    if (uri.match(re)) {
        return uri.replace(re, '$1' + key + "=" + value + '$2');
    }
    else {
        return uri + separator + key + "=" + value;
    }
}


/*
 * Wyswig configuration
 */
var ctrlWyswigEditors = new Array();


function showLoading(showFormLoader) {
    if (showFormLoader) {
        Page.dialogIndicator.stop(true, true).fadeIn();
    } else
        Page.ajaxIndicator.stop(true, true).fadeIn();
}

function hideLoading() {
    if (Page.dialogIndicator.is(":visible"))
        Page.dialogIndicator.stop(true, true).hide();

    Page.ajaxIndicator.stop(true, true).fadeOut();
    return;
}
var parsePage;
var Page = {
    widthChange: 1300,
    widthLowerChange: 520,
    popupWindow: null,
    beforeSubmit: null,
    beforeSerialize: null,
    afterSubmitCallback: null,
    ajaxIndicator: null,
    dialogIndicator: null,
    doResponsive: true,
    menuStack: new Array(),


    init: function () {
        /*if ($.browser.msie && parseInt($.browser.version, 10) === 8) {
         Page.doResponsive = false;
         }*/

        Page.dialogIndicator = window.top.$("#loading-dialog-indicator");
        Page.ajaxIndicator = window.top.$("#loading-indicator");

        Page.layout();

        $.ajaxSetup({
            cache: false
        });

        $(document).ajaxError(function (e, jqXHR, settings, exception) {


            if (settings.suppressErrors) {
                return;
            }
            if(exception == "abort")
                return;

            var msg;
            if (jqXHR.status === 0)
            {
                msg = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404)
            {
                msg = ('Requested page not found. [404]');
            } else if (jqXHR.status == 500)
            {
                msg = ('Internal Server Error [500].');
            } else if (exception === 'parsererror')
            {
                msg = ('Requested JSON parse failed.');
            } else if (exception === 'timeout')
            {
                msg = ('Time out error.');
            } else if (exception === 'abort')
            {
                msg = ('Ajax request aborted.');
            } else
            {
                msg = ('Uncaught Error.\n' + jqXHR.responseText);
            }



            SerenityCommon.triggerError(msg  + "  <b><small>[ "+ settings.url +" ]</small></b>", jqXHR.responseText);
        })/*.ajaxStart(function () {
                showLoading();
            }).ajaxStop(function (evt, request, settings) {
                hideLoading();
            })*/.ajaxComplete(function (evt, request, settings) {
                Page.checkResponse(request.responseText, settings);
            });

        var doit;
        $(window).resize(function(){
            clearTimeout(doit);
            doit = setTimeout(Page.layout, 100);
        });



    },
    processResponse: function (data) {
        if (Page.checkResponse(data)) {
            if (data.arrow_redirect != undefined) {
                window.location.href = data.arrow_redirect;
            } else {
                window.location.reload();
            }
        }
    },
    checkResponse: function (data, settings) {
        var text = data;//request.responseText

        if (settings.dataType == "json") {
            json = text;
            //json = JSON.parse(text);

            if (json && json.exception != undefined) {
                alert (json.exception )
                Page.showDialog("common-/error?message=" + json.exception.message + "&parameters=" + encodeURIComponent(JSON.stringify(json.exception.parameters)));
                return false;
            }

        }else{
            if ( text && text != undefined ){
                if( text.indexOf("<!--Error-->") != -1 || text.indexOf("Fatal error") != -1 ) {
                    $(document).data('prevent_any_other_calls', '1');

                    SerenityCommon.triggerError('Error ' , text);
                    return false
                }
            }
        }
        return true;
    },


    /**
     * Handler for pageReload
     * @param force
     * @return
     */
    reloadHandler: function (force) {

        setTimeout(function () {
            var widgets = Serenity.getWidgets()
            if (!$.isEmptyObject(widgets)) {
                for (id in widgets) {
                    if (widgets[id].className == "SerenityTable")
                        widgets[id].refreshBody();
                }
            } else {
                window.location.reload();
            }
        }, 200);
    },

    layout: function () {

        var heightCont = $("#schema-height-container");
        var mainMenu = $("#main-menu");
        //mainMenu.css({"float": "left", "width": "180px"});
        heightCont.height($(window).height() - $("#top-menu").height()-8);
        //heightCont.width($(window).width() - mainMenu.width() - 15);//.css("border", "solid 1px");
        mainMenu.height(heightCont.height() -16);



        if( $(window).width() < 1040 ){
            //mainMenu.hide();
            heightCont.css('margin-left', '23px');

            $("#main-menu-container").addClass("menu-floating");
            $("#menu-indicator").parent().mouseenter(function() {
                return $(this).stop(true, false).animate({
                    left: 0
                });
            }).mouseleave(function() {
                return $(this).stop(true, false).animate({
                    left: -230
                });
            });

            $("#top-menu").find(".title").css("width", "auto");
        }



    },

    getDialogUrl: function (element) {
        div = $(element).parents(".admin-dialog-window:eq(0)");
        return div.attr('data-url');
    },

    reloadDialog: function (element, address) {

        Page.closeLastDialog();
        Page.showDialog(address);

        return;

        div = $(element).parents(".admin-dialog-window:eq(0)");

        if(address == null)
            address = div.attr("data-url");
        alert(address);

        div
            .attr('data-url', address)
            .load(address, function () {
                parsePopup(div);
                parsePage(div);
            });
    },

    setTopMenu: function(menu){


        if(menu.length == 0){
            return;
        }

        menu.find(".navbar-brand").addClass('current')

        if( $(window).width() > 1200 ){
            var topNav = $(".top-page-nav");
        }else{
            var topNav = $('.top-page-nav-under');
            if(topNav.length == 0 ){

                var topNav = $('<div/>').addClass('top-page-nav').addClass('top-page-nav-under').insertAfter($("#top-menu"));

                var heightCont = $("#schema-height-container");
                var mainMenu = $("#main-menu");
                heightCont.height($(window).height() - $("#top-menu").height() - 50 -8);
                mainMenu.css('margin-top', '-50px');
            }

        }

        var prevNav  = topNav.find(".navbar-brand").clone(true).addClass('non-active');
        if( prevNav.length > 0 ){
            prevNav.insertBefore( menu.find(".navbar-brand") );
        }
        //$(".navbar-brand",topToolbar).attr("href", href).click(function(){ loadPage($(this).attr("href")); return false; });
        topNav.find("select").select2("destroy");
        Page.menuStack.push(topNav.children().clone(true));
        topNav.html("");

        menu.appendTo(topNav);
    },

    closeTopMenu: function(){
        if(Page.menuStack.length>0){

            if( $(window).width() > 1200 ){
                var topNav = $(".top-page-nav");
            }else{
                var topNav = $('.top-page-nav-under');
            }


            topNav.html("");
            //console.dir(Page.menuStack);
            topNav.append(Page.menuStack.pop());
            topNav.find("select").select2({
                minimumResultsForSearch: 10,
                disable_search: ($(this).find("option").length < 15),
                dropdownAutoWidth: true,
                adaptContainerCssClass: function(clazz){
                if(clazz != "form-control")
                    return clazz;
                }
            });
        }
    },

    showDialog: function (address) {
        //showLoading(true);
        var div = $("<div></div>");
        var cont = $("body")//
        var heightContainer = $("#schema-height-container");
        var dialogs = $(".admin-dialog-window");

        var heightContainer = $("#schema-height-container");
        var height = heightContainer.outerHeight(false);
        var width = heightContainer.outerWidth(false);


        div.addClass('admin-dialog-window')
            .attr('data-url', address)
            .css({
                top: heightContainer.offset().top,
                left: heightContainer.offset().left,
                width: width,
                height: height
            });


        cont
            .append(div);

        div.load(address, function () {
            parsePopup(div);
            parsePage(div);

            Page.setTopMenu($("[data-serenity-id='top-nav']", div));





        });
        return;
    },
    closeLastDialog:function(){
        var dialogs = $(".admin-dialog-window");
        if(dialogs.length == 1)
            $(".admin-dialog-shadow").remove();

        $(".admin-dialog-window").last().remove();

        Page.closeTopMenu();




    }




};


var loadPage;
$(function () {

    Page.init();

    SerenityWidget.classOn("", "htmlLoaded", function () {
        $("select",this.host).each(function (index, el) {
            if($(el).is(".no-select2")) return;

            $(el).addClass(".no-select2").select2({
                minimumResultsForSearch: 10,
                disable_search: ($(el).find("option").length < 15),
                dropdownAutoWidth: true,
                adaptContainerCssClass: function(clazz){
                    if(clazz != "form-control")
                        return clazz;
                }
            });
        });
    });

    SerenityWidget.classOn( "SerenityModal", "opened", function(){
        var modal = this;
        var heightContainer = $("#schema-height-container");
        var height = heightContainer.outerHeight(false) - 5;
        var width = heightContainer.find(">div").outerWidth(false);




        $(".modal", this.host).css({
            top: heightContainer.offset().top + 5,
            left: heightContainer.offset().left
            //width: width,
            //overflow: 'hidden'
        });
        $(".modal-dialog", this.host).css({
            'margin': 0,
            'min-height': height,
            'width': width
       });


        $(".modal-content", this.host).css({
           'box-shadow': 'none',
            'border': 'none',
            'border-bottom': 'solid 1px grey',
            'border-radius': 0,
            'width': width, //$(window).width() - 188,
            'margin-top': 0,
            //'padding-top': 15,
            'min-height': height
        });
        $(".modal-body", this.host).css({
            width: width
        });

        $(".modal-header", this.host).hide();

        var title = $(".modal-title", this.host).text();
        var nav = $('<nav />').addClass('navbar').append(
            $('<div />').addClass('navbar-header').append(
                $('<a />').addClass('navbar-brand').text(title)
            )
        ).append('<div class="collapse navbar-collapse"><ul class="nav navbar-nav"></ul></div>');

        $(".modal-footer", this.host).children().each(function(index,el){
            nav.find('.nav').append(
                $("<li>").append($(el))
            );

        });
        nav.find('.nav').find('.serenity-widget').each(function(){
            var widget = window.Serenity.get( this);
            widget.refreshTargets = [modal.id, widget.id ];
        });

        Page.setTopMenu(nav);
        $(".modal-header,.modal-footer", this.host).hide();

        //heightContainer.find(">div").height($(".modal-dialog", this.host).height());
    });




    SerenityWidget.classOn( "SerenityModal", "closed", function(){
        Page.closeTopMenu();
    });

    parsePage = function () {


        $("select").each(function (index, el) {
            if($(el).is(".no-select2")) return;
            $(el).addClass(".no-select2").select2({
                minimumResultsForSearch: 10,
                disable_search: ($(el).find("option").length < 15),
                dropdownAutoWidth: true,
                adaptContainerCssClass: function(clazz){
                    if(clazz != "form-control")
                        return clazz;
                }
            });
        });

        $(".ui-fit-to-parent").each(function () {
            var el = $(this);
            var parent = el.parent();
            var height = parent.height();
            parent.children().each(function () {
                var t = $(this);
                if (!t.is(el))
                    height -= t.outerHeight(true);
            });
            el.height(height - ( el.outerHeight(true) - el.outerHeight(false) ));
        });

        $(".ctrl-table-quick-search input[type='text']").eq(0).focus().mouseenter(function(){

        });

        $(".admin-link").click(function (e) {
            e.preventDefault();
            loadPage($(this).attr("href"));
        });


        $(".dropdown-toggle").mouseenter(function (e) {

            var menu = $(this).next('.dropdown-menu'),
                mousex = e.pageX + 20, //Get X coodrinates
                mousey = e.pageY + 20, //Get Y coordinates
                menuWidth = menu.width(), //Find width of tooltip
                menuHeight = menu.height(), //Find height of tooltip
                menuVisX = $(window).width() - (mousex + menuWidth), //Distance of element from the right edge of viewport
                menuVisY = $(window).height() - (mousey + menuHeight); //Distance of element from the bottom of viewport

            if (menuVisX < 20) { //If tooltip exceeds the X coordinate of viewport
                //menu.css({'left': '-89px'});
            }
            if (menuVisY < 20) { //If tooltip exceeds the Y coordinate of viewport
                menu.css({
                    'top': 'auto',
                    'bottom': '100%'
                });
            }
        });


        $(".switch-lang").change(function () {

            if($(".admin-dialog-window").length > 0){
                Page.reloadDialog($(".admin-dialog-window").last().children(), updateQueryStringParameter($(this).attr('data-url'), "currLang", $(this).val()));
            }else{

                loadPage(updateQueryStringParameter($(this).attr('data-url'), "currLang", $(this).val()));
            }
        });


    };
    parsePage();

    $("body").delegate(".admin-dialog", "click", function (e) {
        e.preventDefault();
        Page.showDialog($(this).attr("href"));
    });


    var loadCont = $("#schema-height-container>div");
    var hashEventEnabled = true;
    loadPage = function (href) {
        if (!href)
            return;

        if (href == "#")
            return;

        loadCont.css({opacity: '0.5'});
        $(".popup-window-iframe").remove();
        hashEventEnabled = false;
        $.bbq.pushState({ currPage: href });

        $("#top-menu .active").removeClass("active");

        loadCont.load(href, function () {
            Page.closeLastDialog();


            loadCont.css({opacity: 1});
            $("#top-menu a[href='" + href + "']").parents("li").addClass("active");
            hashEventEnabled = true;

            var topToolbar = $("[data-serenity-id='top-nav']", "#schema-height-container");

            //dialog menu extended
            parsePage();
            Serenity.scanForWidgets();


            $(".search-query","#main-menu").remove();
            if(topToolbar.length > 0){

                Page.setTopMenu(topToolbar);

                //topToolbar.appendTo(topNav);
                var table = false;
                try{
                    table = Serenity.get($("table:eq(0)","#schema-height-container" ))
                }catch(e){}

                if(table.className ==  "SerenityTable"){
                    //table.fitToParent($("#schema-height-container").height());
                }



                var search = $(".search-query");//.prependTo("#main-menu").focus();
                var target = search.attr('data-target');
                if(target)
                    table = Serenity.get(target);

                $(".w-table-global-search").remove();


                var timer = 0




               var advancedMenu = $(' <ul class="dropdown-menu"  style="top: 10px;left: 20px;"><li><a href="#"><i class="fa fa-arrow-circle-down"></i> Zaawansowane</a></li></ul>');
                /*advancedMenu.css({
                    top: search.top() +
                });*/
                if(search.length > 0){
                    advancedMenu.appendTo($("body")).css({
                        top: search.offset().top + search.outerHeight(),
                        left: search.offset().left ,
                        width: search.outerWidth()
                    });


                    search.on( 'keyup', function(e){
                        var field = $(e.currentTarget);

                        var target = field.attr('data-target');
                        if(target)
                            table = Serenity.get(target);



                        if (e.keyCode == 40) {
                            advancedMenu.show();
                            table.unFitToParent();


                            var search = Serenity.get('advanced-search')
                            if( search.get("generate") == 0 )
                                search.set("generate", 1).refresh({},{},function(){ search.show(); })
                            else
                                search.show(); //;

                            return false;
                        }
                        if (e.keyCode == 38) {
                            advancedMenu.hide();
                            Serenity.get('advanced-search').hide(function(){
                                table.fitToParent();
                            });

                             //set("generate", 0).refresh();
                            return false;
                        }

                        clearTimeout(timer)
                        timer = setTimeout( function(){
                            //if(field.val().length>1)
                            table.refresh({}, {globalSearch: field.val(), page: 1})
                        }, 300);
                    });
                }
            }else{

            }



        });
    }

    $(window).on('hashchange', function () {
        if (hashEventEnabled) {
            var state = $.bbq.getState("currPage");
            if (state) {
                loadPage(state);
            }
        }

    });



    $("h3", "#main-menu").on('click', function (e) {
        var cont = $(this).parent();
        var closed = $.cookie('arrow-admin-menu');
        closed = closed?JSON.parse(closed):new Array();
        if (cont.height() != 34){
            cont.height(34);
            closed.push(cont.index())
        }else{
            cont.height('auto');
            for(var i in closed){
                if(closed[i]==cont.index()){
                    closed.splice(i,1);

                }
            }

        }
        $.cookie('arrow-admin-menu', JSON.stringify(closed), { path: '/' });
    });

    if( $.cookie('arrow-admin-menu')){
        var arr = JSON.parse($.cookie('arrow-admin-menu'));
        for( i in arr){
            $("h3", "#main-menu").eq(arr[i]).parent().height(34);
        }
    }

    $("a", "#main-menu,#top-menu").on('click', function (e) {
        if (!$(this).is(".direct")) {
            e.preventDefault();
            $(".active", "#main-menu,#top-menu").removeClass("active");

            $(this).addClass("active");
            loadPage($(this).attr("href"));
        }
    });


    var state = $.bbq.getState("currPage")
    if (state) {
        loadPage(state);
    }




    $.ytLoad();
    Page.setTopMenu($("[data-serenity-id='top-nav']"));

});


jQuery.script = function (url, options) {

    // allow user to set any option except for dataType, cache, and url
    options = $.extend(options || {}, {
        dataType: "script",
        cache: true,
        url: url
    });

    // Use $.ajax() since it is more flexible than $.getScript
    // Return the jqXHR object so we can chain callbacks
    return jQuery.ajax(options);
};