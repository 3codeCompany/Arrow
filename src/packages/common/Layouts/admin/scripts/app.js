(function () {
}).call(this), function () {
}.call(this), function () {
}.call(this), function () {
    angular.module("app.ui.form.directives", []).directive("uiRangeSlider", [function () {
        return {
            restrict: "A", link: function (scope, ele) {
                return ele.slider()
            }
        }
    }]).directive("uiFileUpload", [function () {
        return {
            restrict: "A", link: function (scope, ele) {
                return ele.bootstrapFileInput()
            }
        }
    }]).directive("uiSpinner", [function () {
        return {
            restrict: "A", compile: function (ele) {
                return ele.addClass("ui-spinner"), {
                    post: function () {
                        return ele.spinner()
                    }
                }
            }
        }
    }]).directive("uiWizardForm", [function () {
        return {
            link: function (scope, ele) {
                return ele.steps()
            }
        }
    }])
}.call(this), function () {
    "use strict";
    angular.module("app.page.ctrls", []).controller("invoiceCtrl", ["$scope", "$window", function ($scope) {
        return $scope.printInvoice = function () {
            var originalContents, popupWin, printContents;
            return printContents = document.getElementById("invoice").innerHTML, originalContents = document.body.innerHTML, popupWin = window.open(), popupWin.document.open(), popupWin.document.write('<html><head><link rel="stylesheet" type="text/css" href="styles/main.css" /></head><body onload="window.print()">' + printContents + "</html>"), popupWin.document.close()
        }
    }])
}.call(this), function () {
    "use strict";
    angular.module("app", ["ngRoute", "ngAnimate", "ui.bootstrap", "mgo-angular-wizard", "ngTagsInput", "app.controllers",  "app.nav",     "app.ui.form.directives",   "app.page.ctrls"]).config(["$routeProvider", function ($routeProvider) {
        var routes;
        return routes = [], $routeProvider.when("/", {redirectTo: (window.appBaseUrl!="/"?window.appBaseUrl:"") + "/dashboard"}).when("/404", {templateUrl: "views/pages/404.html"}).otherwise({
            templateUrl: function () {
                return function () {
                    var date, link;
                    return date = new Date, link = -1 !== window.location.hash.indexOf("?") ? window.location.hash.replace("#", "") + "&__ARROW_FORCE_AJAX__=1&time=" + date.getTime() : window.location.hash.replace("#", "") + "?__ARROW_FORCE_AJAX__=1&time=" + date.getTime()
                }
            }(this)
        })
    }]).run(function ($rootScope, $location,$route, $timeout) {
        

    }(this))
}.call(this), function () {
    "use strict";
    angular.module("app.nav", []).directive("toggleNavCollapsedMin", ["$rootScope", "$timeout", function ($rootScope,$timeout) {

        $rootScope.$on('$routeChangeStart', function() {
            var c = $(".content-container");
            //$("#admin-loading-indicator").width(c.width());
            //$("#admin-loading-indicator").height(c.height());
            document.getElementById("admin-loading-indicator").style.display = 'block';

        });
        $rootScope.$on('$routeChangeSuccess', function () {
            //hide loading gif
            $timeout(function () {
                document.getElementById("admin-loading-indicator").style.display = 'none';
            }, 200);
        });

        return {
            restrict: "A", link: function (scope, ele) {
                var app;
                return app = $("#app"), ele.on("click", function (e) {
                    return app.hasClass("nav-collapsed-min") ? app.removeClass("nav-collapsed-min") : (app.addClass("nav-collapsed-min"), $rootScope.$broadcast("nav:reset")), e.preventDefault()
                })
            }
        }
    }]).directive("collapseNav", [function () {
        return {
            restrict: "A", link: function (scope, ele) {
                var $a, $aRest, $app, $lists, $listsRest, $nav, $window, Timer, prevWidth, updateClass;
                return $window = $(window), $lists = ele.find("ul").parent("li"), $a = $lists.children("a"), $listsRest = ele.children("li").not($lists), $aRest = $listsRest.children("a"), $app = $("#app"), $nav = $("#nav-container"), $a.on("click", function (event) {
                    var $parent, $this;
                    return $app.hasClass("nav-collapsed-min") || $nav.hasClass("nav-horizontal") && $window.width() >= 768 ? !1 : ($this = $(this), $parent = $this.parent("li"), $lists.not($parent).removeClass("open").find("ul").slideUp(), $parent.toggleClass("open").find("ul").slideToggle(), event.preventDefault())
                }), $aRest.on("click", function () {
                    return $lists.removeClass("open").find("ul").slideUp()
                }), scope.$on("nav:reset", function () {
                    return $lists.removeClass("open").find("ul").slideUp()
                }), Timer = void 0, prevWidth = $window.width(), updateClass = function () {
                    var currentWidth;
                    return currentWidth = $window.width(), 768 > currentWidth && $app.removeClass("nav-collapsed-min"), 768 > prevWidth && currentWidth >= 768 && $nav.hasClass("nav-horizontal") && $lists.removeClass("open").find("ul").slideUp(), prevWidth = currentWidth
                }, $window.resize(function () {
                    var t;
                    return clearTimeout(t), t = setTimeout(updateClass, 300)
                })
            }
        }
    }]).directive("highlightActive", [function () {
        return {
            restrict: "A", controller: ["$scope", "$element", "$attrs", "$location", function ($scope, $element, $attrs, $location) {
                var highlightActive, links, path;
                return links = $element.find("a"), path = function () {
                    return $location.path()
                }, highlightActive = function (links, path) {
                    path = "#" + path;
                    var selected = false;
                    angular.forEach(links, function (link) {
                        var $li, $link, href;
                        $link = angular.element(link);
                        $li = $link.parent("li");
                        href = $link.attr("href");
                        $li.hasClass("active") && $li.removeClass("active");
                        0 === path.indexOf(href) ? (selected=true, $('.open ul').hide().parent().removeClass("active open"), $li.parents("li").addClass("open"), $li.parents("ul").show(), $li.parents(".open").addClass("active"), $li.addClass("active")) : void 0
                    })
                    //jesli nie znaleziono sciezki usuwamy ostatni czlon linku i szukamy dalej
                    if(!selected && false){
                        var tmp = path.split("/")
                        tmp.pop()
                        path = tmp.join("/")


                        angular.forEach(links, function (link) {
                            var $li, $link, href;
                            $link = angular.element(link);
                            $li = $link.parent("li");
                            href = $link.attr("href");
                            tmp = href.split("/")
                            tmp.pop()
                            href = tmp.join("/")
                            $li.hasClass("active") && $li.removeClass("active");
                            0 === path.indexOf(href) ? (selected=true, $li.parents(".open").addClass("active"), $li.addClass("active")) : void 0
                        })
                    }
                }, highlightActive(links, $location.path()), $scope.$watch(path, function (newVal, oldVal) {
                    return newVal !== oldVal ? highlightActive(links, $location.path()) : void 0
                })
            }]
        }
    }]).directive("toggleOffCanvas", [function () {
        return {
            restrict: "A", link: function (scope, ele) {
                return ele.on("click", function () {
                    return $("#app").toggleClass("on-canvas")
                })
            }
        }
    }])
}.call(this), function () {


}.call(this), function () {
    "use strict";
    angular.module("app.localization", []).factory("localize", ["$http", "$rootScope", "$window", function ($http, $rootScope, $window) {
        var localize;
        return localize = {
            language: "", url: void 0, resourceFileLoaded: !1, successCallback: function (data) {
                return localize.dictionary = data, localize.resourceFileLoaded = !0, $rootScope.$broadcast("localizeResourcesUpdated")
            }, setLanguage: function (value) {
                return localize.language = value.toLowerCase().split("-")[0], localize.initLocalizedResources()
            }, setUrl: function (value) {
                return localize.url = value, localize.initLocalizedResources()
            }, buildUrl: function () {
                return localize.language || (localize.language = ($window.navigator.userLanguage || $window.navigator.language).toLowerCase(), localize.language = localize.language.split("-")[0]), "i18n/resources-locale_" + localize.language + ".js"
            }, initLocalizedResources: function () {
                var url;
                return url = localize.url || localize.buildUrl(), $http({method: "GET", url: url, cache: !1}).success(localize.successCallback).error(function () {
                    return $rootScope.$broadcast("localizeResourcesUpdated")
                })
            }, getLocalizedString: function (value) {
                var result, valueLowerCase;
                return result = void 0, localize.dictionary && value ? (valueLowerCase = value.toLowerCase(), result = "" === localize.dictionary[valueLowerCase] ? value : localize.dictionary[valueLowerCase]) : result = value, result
            }
        }
    }]).directive("i18n", ["localize", function (localize) {
        var i18nDirective;
        return i18nDirective = {
            restrict: "EA", updateText: function (ele, input, placeholder) {
                var result;
                return result = void 0, "i18n-placeholder" === input ? (result = localize.getLocalizedString(placeholder), ele.attr("placeholder", result)) : input.length >= 1 ? (result = localize.getLocalizedString(input), ele.text(result)) : void 0
            }, link: function (scope, ele, attrs) {
                return scope.$on("localizeResourcesUpdated", function () {
                    return i18nDirective.updateText(ele, attrs.i18n, attrs.placeholder)
                }), attrs.$observe("i18n", function (value) {
                    return i18nDirective.updateText(ele, value, attrs.placeholder)
                })
            }
        }
    }]).controller("LangCtrl", ["$scope", "localize", function ($scope, localize) {

    }])
}.call(this), function () {
    "use strict";
    angular.module("app.controllers", []).controller("AppCtrl", ["$scope", "$rootScope", function ($scope, $rootScope) {
        var $window;
        return $window = $(window), $scope.main = {brand: "SOR Panel", name: "Lisa Doe"}, $scope.$on("$viewContentLoaded", function () {
            return function () {
                return "undefined" != typeof window.Page && null !== Page ? Page.onLoad() : void 0
            }
        }(this)), $scope.admin = {layout: "wide", menu: "vertical", fixedHeader: !0, fixedSidebar: !0}, $scope.$watch("admin", function (newVal, oldVal) {
            return (newVal.menu !== oldVal.menu || newVal.layout !== oldVal.layout) && $window.trigger("resize"), "horizontal" === newVal.menu && "vertical" === oldVal.menu ? void $rootScope.$broadcast("nav:reset") : newVal.fixedHeader === !1 && newVal.fixedSidebar === !0 ? (oldVal.fixedHeader === !1 && oldVal.fixedSidebar === !1 && ($scope.admin.fixedHeader = !0, $scope.admin.fixedSidebar = !0), void(oldVal.fixedHeader === !0 && oldVal.fixedSidebar === !0 && ($scope.admin.fixedHeader = !1, $scope.admin.fixedSidebar = !1))) : (newVal.fixedSidebar === !0 && ($scope.admin.fixedHeader = !0), void(newVal.fixedHeader === !1 && ($scope.admin.fixedSidebar = !1)))
        }, !0), $scope.color = {primary: "#1BB7A0", success: "#94B758", info: "#56BDF1", infoAlt: "#7F6EC7", warning: "#F3C536", danger: "#FA7B58"}
    }]).controller("HeaderCtrl", ["$scope", function ($scope) {
        return $scope.introOptions = {steps: [{element: "#step1", intro: "<strong>Head up!</strong> You can change the layout here", position: "bottom"}, {element: "#step2", intro: "Select a different language", position: "right"}, {element: "#step3", intro: "Runnable task App", position: "left"}, {element: "#step4", intro: "Collapsed nav for both horizontal nav and vertical nav", position: "right"}]}
    }]).controller("NavContainerCtrl", ["$scope", function () {
    }]).controller("NavCtrl", ["$scope", "filterFilter", function ($scope,  filterFilter) {

    }]).controller("DashboardCtrl", ["$scope", function () {
    }])
}.call(this);