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
            //AjaxForm.init(context[0]);
            if (typeof(ReactHelper) != "undefined")
                ReactHelper.initComponents(context[0]);
            else {
                console.warn("React helper not found");
            }

        };

        SerenityWidget.classOn("", "htmlLoaded", function () {
            if (typeof(ReactHelper) != "undefined")
                ReactHelper.initComponents(this.host[0]);
            else {
                console.warn("React helper not found");
            }

        });

        var loadingTimeout;
        loadPage = function (url) {
            loadingTimeout = setTimeout(function () {
                $("#admin-loading-indicator").show();
            }, 400);

            url = url.replace("#", "");
            window.location.hash = url;
            $.get(url, function (data) {
                var container = $(".content-container");
                container.html(data);
                onPageLoaded(container)
                clearTimeout(loadingTimeout);
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




});