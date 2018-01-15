<?php

namespace Arrow\CMS\Controllers;


use App\Controllers\BaseController;
use Arrow\Access\Models\Auth;
use Arrow\Common\AdministrationLayout;
use Arrow\Common\AdministrationPopupLayout;
use Arrow\Common\BreadcrumbGenerator;
use Arrow\Common\Layouts\EmptyLayout;
use Arrow\Common\Links;
use Arrow\Common\PopupFormBuilder;
use Arrow\Common\TableDatasource;
use Arrow\Media\Element;
use Arrow\Media\ElementConnection;
use Arrow\Media\MediaAPI;
use Arrow\Models\Dispatcher;
use Arrow\Models\Operation;
use Arrow\Models\View;
use Arrow\ORM\Persistent\Criteria;
use Arrow\Package\Application\PresentationLayout;
use Arrow\RequestContext;
use Arrow\Router;
use Arrow\Translations\Models\Translations;

;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */
class Controller extends BaseController
{



    public function cmsPageRequest($path, RequestContext $request)
    {
        $page = Criteria::query(Page::getClass())
            ->c(Page::F_REWRITE_NAME, $path)
            ->findFirst();

        $name = ucfirst(str_replace("_", " ", substr($path, 1)));

        if (empty($page)) {
            $setting = \Arrow\Models\Settings::getDefault()->getSetting("cms.pages.dynamicAdd");
            if (!$setting) {
                return null;
            }
            $page = new Page(array(
                Page::F_PARENT_ID => 1,
                Page::F_NAME => $name,
                Page::F_REWRITE_NAME => $path,
                Page::F_TYPE => "page",
                Page::F_CONTENT => "Content of " . $name,
                Page::F_ACTIVE => 1,
                Page::F_HEADER => "Header of " . $name
            ));
            $page->save();

        }
        $pageView = \Arrow\Models\Dispatcher::getDefault()->get("cms::front/page");
        $pageView->setLayout(new PresentationLayout());
        $pageView->assign("page", $page);
        return $pageView;
    }


    public static function pageNotFound(Action $view)
    {

        $user = Auth::getDefault()->getUser();

        $page = Criteria::query(Page::getClass())
            ->c(Page::F_REWRITE_NAME, "/" . Router::getActionName())
            ->findFirst();


        if ($page) {
            $page = Dispatcher::getDefault()->get("cms::/front/cmsPage");
            print $page->fetch();
            exit();
            return true;
        } else {
            $_404 = Dispatcher::getDefault()->get("cms::/common/404");
            print $_404->fetch();
            exit();
            return true;
        }
    }

    public function common_cmsPage(Action $view, RequestContext $request)
    {
        $page = Criteria::query(Page::getClass())
            ->c(Page::F_REWRITE_NAME, $request["path"])
            ->findFirst();

        Translations::translateObject($page);

        if (empty($page)) {
            exit($this->cmsPageRequest($request["path"], $request)->fetch());
        }
        MediaAPI::prepareMedia(array($page));

        $view->setLayout(new PresentationLayout());

        $view->assign("page", $page);
    }


    public function pages_structure(Action $view, RequestContext $request)
    {
        $view->setLayout(new AdministrationLayout(), new EmptyLayout());
    }

    private function canEdit(Action $view)
    {
        $l = $view->getLayout();
        if ($l instanceof AdministrationLayout || $l instanceof AdministrationPopupLayout) {
            return false;
        }

        $user = Auth::getDefault()->getUser();
        if (!$user) {
            return false;
        }
        $sum = $user->getAccessGroupsSum();
        if ($sum & 2 || $sum & 4) {
            return true;
        }
        return false;
    }








    public function createPage(Operation $op, RequestContext $request)
    {
        $name = str_replace(array("/", "_"), array(" ", " "), "/" . $request["rewrite"]);
        $name = ucfirst($name);

        $page = Page::createIfNotExists(
            array(
                Page::F_REWRITE_NAME => "/" . $request["rewrite"],
                Page::F_CONTENT => "<p>This is content of `{$name}`</p>",
                Page::F_NAME => $name,
                Page::F_HEADER => $name,
                Page::F_PARENT_ID => 1,
                Page::F_ACTIVE => 1,
                Page::F_SHOWINMENU => 1,
                Page::F_SITEMAP => 1,
            )
        );

        return $page["id"];

    }






    public function pages_add(Action $view, RequestContext $request, $package)
    {
        $view->setLayout(new AdministrationPopupLayout(), new EmptyLayout());

        $form = Form::_new("add")
            ->setRequired(array("name", "rewrite_name"));

        $list = FieldsList::create()
            ->row()
            ->addField("Kontener nadrzędny", Select::_tree("parent_id", Page::get()->order("sort")->find(true), 1))
            ->addField("Typ", SwitchF::_new("type", array("page" => "Strona", "container" => "Kontener"), "container"))
            ->rowEnd()
            ->row()
            ->addField("Nazwa", Text::_new("name"))
            ->addField("Nazwa rewrite", Text::_new("rewrite_name"))
            ->rowEnd()
            ->addField("Aktywna", SwitchF::_bool("active"));

        $builder = PopupFormBuilder::_new($form, $list, Page::getClass(), null)
            ->setTitles("Dodaj stronę", "Edytuj stronę");
        $view->assign("builder", $builder);

    }

    public function news_list(Action $view, RequestContext $request)
    {
        $view->setLayout(new AdministrationLayout(), new EmptyLayout());
        $columns = (new ColumnList)
            ->simple("id", "id")
            ->simple("date", "Data")
            ->simple("title", "Tytuł")
            ->add(
                Menu::_new("Opcje")
                    ->add(Links::edit())
                    ->add()
                    ->add(Links::delete(News::getClass()))
            );
        $ds = TableDatasource::fromClass(News::getClass());
        $ds->c("type", $request["type"] ? $request["type"] : 2);
        $ds->c("partner_id", 1);
        $ds->c("language", ["all", $request["lang"] ? $request["lang"] : "pl"], Criteria::C_IN);

        $table = Table::create("news", $ds, $columns)
            ->setToolbar(Toolbar::_new("", [Links::add()]));


        if ($table->getState(Table::STATE_GLOBAL_SEARCH)) {
            $ds->addSearchCondition(["id", "title", "date"], "%" . $table->getState(Table::STATE_GLOBAL_SEARCH) . "%");
        }

        $view->assign("table", $table);

    }

    public function news_edit(Action $view, RequestContext $request)
    {
        $view->setLayout(new AdministrationPopupLayout(), new EmptyLayout());
        $news = Criteria::query(News::getClass())->findByKey($request["key"]);
        $form = Form::_new("news", $news)
            ->setRequired(array("title", "date"))
            ->addValidator(function ($values) {
                if (!isset($_POST["currentLanguage"])) {
                    if (empty($values["type"])) {
                        return ["type" => "Podaj typ artykułu"];
                    }

                }
                return true;
            });


        $type = SwitchF::_new("type", array(1 => "Wydarzenie", 2 => "Aktualność", 3 => "Newsletter"), 1)
            ->setNamespace("data")
            ->setJSOnChange("Serenity.get(this).refresh()");

        $fields = FieldsList::create()
            ->section("Dane podstawowe", "edit")
            ->addField("Typ", $type, ["cols" => 10])
            ->addField("Nazwa", Text::_new("title"))
            ->addField("Link", Text::_new("link"), ["display" => $form->getFieldValue($type) == 1 || !$form->getFieldValue($type)])
            ->row()
            ->addField("Data", Date::_new("date", "Podaj datę"))
            ->addField("Data zakończenia (opcjonalnie)", Date::_new("date2", "Podaj datę"), ["display" => $form->getFieldValue($type) == 1 || !$form->getFieldValue($type)])
            ->rowEnd()
            ->addField("Aktywny", SwitchF::_bool("active", 1))
            ->addField("Język", SwitchF::_new("language", array("all" => "Wszystkie", "pl" => "Polski", "en" => "Angielski"), "all"), ["cols" => 10])
            //->addField(null,Hidden::_new("partner_id",1))
            //->addField("Plik", File::_new("files") )
            ->addField("Treść krótka", Textarea::_new("content_short")->setBig())
            ->addField("Treść", Textarea::_new("content")->setBig());

        $view->assign("builder", PopupFormBuilder::_new($form, $fields, News::getClass(), $news)->setTitles("Dodaj wydarzenie", "Edytuj wydarzenie"));

    }

    public function news_save( $action, RequestContext $request)
    {
        $data = $request["data"];
        unset($data["files"]);
        if (empty($data["date2"])) {
            unset($data["date2"]);
        }
        if ($data["id"]) {
            $news = Criteria::query(News::getClass())->findByKey($data["id"]);
            $news->setValues($data);
            $news->save();
        } else {
            unset($data["id"]);
            $news = News::create($data);
        }

        if (isset($_FILES["data"])) {

            MediaAPI::setObjectFile($news, "files", $_FILES["data"]["name"]["files"], $_FILES["data"]["tmp_name"]["files"]);
        }

        return true;
    }


    public function news_plugins_single(Action $view, RequestContext $request)
    {
        $view->setLayout(new PresentationLayout());
        $news = Criteria::query(News::getClass())->findByKey($request["id"]);
        Translations::translateObject($news);
        MediaAPI::prepareMedia([$news]);
        $view->assign("news", $news);

        $news = Criteria::query(News::getClass())->c("id", $request["id"], Criteria::C_NOT_EQUAL)->find();
        $view->assign("newsList", $news);
    }

    public function news_plugins_list(Action $view, RequestContext $request)
    {
        $view->setLayout(new PresentationLayout());


    }

}

// Helper functions

function exit_status($str)
{
    echo json_encode(array('status' => $str));
    exit;
}

function get_extension($file_name)
{
    $ext = explode('.', $file_name);
    $ext = array_pop($ext);
    return strtolower($ext);
}
