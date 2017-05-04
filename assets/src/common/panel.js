/**
 * Created by artur on 19.12.2016.
 */

require(["jquery"], function () {

    $(".content-container").css('min-height', $(window).height() - $("#header").height());
    $(".content-container").css('height', $(window).height() - $("#header").height());

    var select2 = function (el) {
        $("select", el).each(function (index, el) {
            if ($(el).is(".no-select2")) return;

            $(el).addClass(".no-select2").select2({
                minimumResultsForSearch: 10,
                disable_search: ($(el).find("option").length < 15),
                dropdownAutoWidth: true,
                adaptContainerCssClass: function (clazz) {
                    if (clazz != "form-control")
                        return clazz;
                }
            });
        });
    };


    require(["controls", "bootstrap", "select2"], function () {

        var loadPage;

        $(".content-container").on('click', "a[href*='#']", function (e) {
            if ($(this).data("toggle") != "tab") {
                e.preventDefault();
                //loadPage($(this).attr("href"));
                window.location.hash = $(this).attr("href");

            }
        })


        var onPageLoaded = function (context) {
            select2(context);
            Serenity.scanForWidgets(context);
            if(typeof(ReactHelper) != "undefined")
                ReactHelper.initComponents(context[0]);
            else{
                console.warn("React helper not found");
            }

        };

        SerenityWidget.classOn("", "htmlLoaded", function () {
             if(typeof(ReactHelper) != "undefined")
                ReactHelper.initComponents(this.host[0]);
            else{
                console.warn("React helper not found");
            }

        });

        loadPage = function (url) {
            $("#admin-loading-indicator").show();
            url = url.replace("#", "");
            window.location.hash = url;
            $.get(url, function (data) {
                var container = $(".content-container");
                container.html(data);
                onPageLoaded(container)
                $("#admin-loading-indicator").hide();
            });
        }


        $(window).on('hashchange', function () {
            loadPage(window.location.hash);
        });

        if (!window.location.hash)
            window.location.hash = (window.appBaseUrl != "/" ? window.appBaseUrl : "") + "dashboard";
        else
            loadPage(window.location.hash);


        $("#nav>li>a").click(function (e) {
            e.preventDefault();
            var link = $(this);
            $("#nav>li.open").removeClass("open");
            $("#nav>li>ul").slideUp();
            link.parent().addClass("open");
            link.next().slideDown();

        });
        $("#nav>li>ul>li>a").click(function (e) {
            e.preventDefault();
            $("#nav>li>ul>li.active").removeClass("active");
            $(this).parent().addClass("active");
            window.location.hash = $(this).attr("href").replace("#", "");
        });

        $(".sidebar-collapse,.toggle-min").click(function (e) {
            e.preventDefault();
            $.get("admin/changeUserSetting", {setting: 'sidebar-collapse'}, function () {
                $("body").toggleClass('nav-collapsed-min');
            });
        });

        $(".theme-item").click(function () {
            $.get("admin/changeUserSetting", {setting: 'admin-theme', value: $(this).attr("data-theme")}, function () {
                window.location.reload();
            });
        });


         SerenityWidget.classOn('SerenityFilterPanel', 'opened', function () {
            $('.serenity-widget-table,.serenity-widget-filterspresenter').css('margin-right', '245px');
         });
         SerenityWidget.classOn('SerenityFilterPanel', 'closed', function () {
            $('.serenity-widget-table,.serenity-widget-filterspresenter').css('margin-right', '0');
         });

    });


    if (false) {


        Page = {
            onLoad: function () {
                setTimeout(function () {
                    SerenityWidget.classOn('SerenityFilterPanel', 'opened', function () {
                        $('.serenity-widget-table,.serenity-widget-filterspresenter').css('margin-right', '245px');
                    });
                    SerenityWidget.classOn('SerenityFilterPanel', 'closed', function () {
                        $('.serenity-widget-table,.serenity-widget-filterspresenter').css('margin-right', '0');
                    });
                    //$("#content").height($(window).height()- $(".top-header").height());
                    window.Serenity.scanForWidgets();


                    select2($("body"))
                    $(".panel-collapse").each(function (index, el) {
                        var el = $(el);
                        if (!el.is(".panel-collapse-opened"))
                            $(">.panel-body", el).hide();
                        $(">.panel-heading", el).css('cursor', 'pointer')
                            .click(function () {
                                $(this).next().toggle();
                            })
                    });

                    var fpanel = $(".serenity-widget-filterpanel #filter-panel");
                    if (typeof (fpanel.offset()) != 'undefined') {
                        fpanel.css('min-height', ($(window).height() - fpanel.offset().top) + 'px');
                    }

                    var scroll = $(".scroll-to");
                    if (scroll.length > 0) {
                        $('html,body').animate({
                                scrollTop: scroll.offset().top
                            },
                            'slow');
                    }


                }, 500);
                //$("body").animate({scrollTop:0}, '500');
                $(".content-container").css('min-height', $(window).height() - $("#header").height());
                $(".content-container").css('height', $(window).height() - $("#header").height());


                $.ajaxSetup({
                    beforeSend: function () {
                        $("#admin-loading-indicator").show();

                    },
                    complete: function () {
                        $("#admin-loading-indicator").hide();
                    }
                });
            }
        };

        SerenityWidget.classOn("", "htmlLoaded", function () {
            select2(this.host)
        });


        SerenityWidget.classOn("SerenityModal", "opened", function () {
            var modal = this;
            modal.host.hide();
            window.location.href += "_modal";
            /*var heightContainer = $("#schema-height-container");
             var height = heightContainer.outerHeight(false) - 5;
             var width = heightContainer.find(">div").outerWidth(false);*/
            //$(".modal").hide();

            $("#content").html(" ").append($(".modal-body", this.host).children());

            $(".modal-body", this.host).remove();
            select2($("#content"));
            $("body").animate({scrollTop: 0}, '500');

            return;

            $(".modal").hide();

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

            $(".modal-footer", this.host).children().each(function (index, el) {
                nav.find('.nav').append(
                    $("<li>").append($(el))
                );

            });
            nav.find('.nav').find('.serenity-widget').each(function () {
                var widget = window.Serenity.get(this);
                widget.refreshTargets = [modal.id, widget.id];
            });

            Page.setTopMenu(nav);
            $(".modal-header,.modal-footer", this.host).hide();

            //heightContainer.find(">div").height($(".modal-dialog", this.host).height());
        });

    }


});