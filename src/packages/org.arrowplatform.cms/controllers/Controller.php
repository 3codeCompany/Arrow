<?php

namespace Arrow\Package\CMS;


use Arrow\Controls\API\Components\Toolbar;
use Arrow\Controls\API\Forms\Fields\Button;
use Arrow\Controls\API\Forms\Fields\File;
use Arrow\Controls\API\Forms\Fields\Files;
use Arrow\Controls\API\Forms\Fields\Helpers\BoolSwitch;
use Arrow\Controls\API\Forms\Fields\Date;
use Arrow\Controls\API\Forms\Fields\Hidden;
use Arrow\Controls\API\Forms\Fields\Select;
use Arrow\Controls\API\Forms\Fields\SwitchF;
use Arrow\Controls\API\Forms\Fields\Text;
use Arrow\Controls\API\Forms\Fields\Textarea;
use Arrow\Controls\API\Forms\Fields\Wyswig;
use Arrow\Controls\API\Forms\FieldsList;
use Arrow\Controls\API\Forms\Form;
use Arrow\Controls\API\Forms\FormBuilder;
use Arrow\Controls\API\Table\ColumnList;
use Arrow\Controls\API\Table\Columns\Menu;
use Arrow\Models\Dispatcher;
use Arrow\Models\Operation;
use Arrow\Models\View;
use Arrow\ORM\Criteria,
    \Arrow\Package\Access\Auth,
    \Arrow\ViewManager, \Arrow\RequestContext;
use Arrow\Package\Access\AccessGroup;
use Arrow\Package\Application\PresentationLayout;
use Arrow\Controls\API\Forms\BuilderSchemas\Bootstrap;
use Arrow\Package\Common\AdministrationLayout;
use Arrow\Package\Common\AdministrationPopupLayout;
use Arrow\Package\Common\BreadcrumbGenerator;
use Arrow\Package\Common\EmptyLayout;
use Arrow\Package\Common\Links;
use Arrow\Package\Common\PopupFormBuilder;
use Arrow\Package\Common\TableDatasource;
use Arrow\Package\Common\Translations;
use Arrow\Package\Media\Element;
use Arrow\Package\Media\ElementConnection;
use Arrow\Package\Media\MediaAPI;
use Arrow\Controls\API\Table\Table;
use Arrow\Router;

/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 04.09.12
 * Time: 14:20
 * To change this template use File | Settings | File Templates.
 */

class Controller extends \Arrow\Package\Application\Controller
{

    private $br = array(
        "__defaults" => array(
            "list" => "Lista",
            // "edit" => "id(Dodaj|[->getName])"
            "edit" => "Edycja"
        ),
        "strict" => array(
            "/news" => "Aktualności",
        )
    );


    public function eventRunAfterAction(Action $view, RequestContext $request)
    {
        parent::eventRunAfterAction($view, $request);


        $breadcrumb = new BreadcrumbGenerator($this->br["strict"], $this->br["__defaults"]);

        $layout = $view->getLayout();

        if ($layout instanceof AdministrationLayout || $layout instanceof AdministrationPopupLayout)
            $layout->setBreadcrumbGenerateor($breadcrumb);

        $view->assign("seoTitle", $this->seoTitle);
        $view->assign("seoDescription", $this->seoDescription);

        $view->assign("editState", false);


    }


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

    public function common_404(Action $view, RequestContext $request, $package)
    {
        $view->setLayout(new PresentationLayout());
    }

    public function common_editToolbar(Action $view, RequestContext $request)
    {
        $page = Criteria::query(Page::getClass())
            ->findByKey($request["page"]);

        MediaAPI::prepareMedia(array($page));

        $view->setLayout(new EmptyLayout());
        $requestedView = Router::getDefault()->get()->getPath();
        $view->assign("requested", $request["requested"]);
        $view->assign("page", $page);


        $editState = isset($_SESSION["org.arrowplatform.package.cms"]["front.page.edition.state"]) ? $_SESSION["org.arrowplatform.package.cms"]["front.page.edition.state"] : false;
        $view->assign("editState", $editState);

    }

    public function page_save_element(IAction $action, RequestContext $request)
    {
        list($class, $id, $field) = explode("|", $request["elementID"]);
        $value = $request["value"];

        $obj = Criteria::query($class)->findByKey($id);
        $obj->setValue($field, $value);
        $obj->save();
        return $obj->getPKey();
    }

    public function pages_edit(Action $view, RequestContext $request, $package)
    {
        $view->setLayout(new AdministrationPopupLayout());
        $page = Criteria::query(Page::getClass())->findByKey($request["id"]);
        $view->assign("page", $page);

        $form = Form::_new("add", $page)
            ->setRequired(array("name", "rewrite_name"));

        $list = FieldsList::create()
            ->tab("Dane i ustawienia", "edit")

            ->section("Dane", "edit")
            ->row()
            ->addField("Nazwa", Text::_new("name"))
            ->addField("Nazwa rewrite", Text::_new("rewrite_name"))
            ->rowEnd()
            ->addField("Aktywna", SwitchF::_bool("active"))
            ->sectionEnd()

            ->section("Położenie i typ", "cogs")
            ->addField("Kontener nadrzędny", Select::_tree("parent_id", Page::get()->order("sort")->find(true), 1))
            ->addField("Typ", SwitchF::_new("type", array("page" => "Strona", "container" => "Kontener"), "container"))
            ->sectionEnd()

            ->section("SEO", "bar-chart")
            ->addField("Tytuł strony", Text::_new("title"))
            ->addField("Słowa kluczowe", Text::_new("keywords"))
            ->addField("Opis", Textarea::_new("description"))
            ->sectionEnd()

            ->tabEnd()

            ->tab("Treści", "book")
            ->addField("Nagłówek", Text::_new("header", $page ? $page["name"] : 'Wprowadź nagłówek'))
            ->addField(null, Wyswig::_new("content")->setWidth(900)->setHeight(500))

            ->tabEnd();


        $builder = PopupFormBuilder::_new($form, $list, Page::getClass(), $page)
            ->setTitles("Dodaj stronę", "Edytuj stronę");
        $view->assign("builder", $builder);
    }

    public function galleries_list(Action $view, RequestContext $request)
    {
        $view->setLayout(new AdministrationLayout());

    }

    public function galleries_edit(Action $view, RequestContext $request)
    {
        $view->setLayout(new AdministrationPopupLayout());
        $gallery = Criteria::query(Gallery::getClass())->findByKey($request["id"]);
        $view->assign("gallery", $gallery);
    }


    public function plugins_gallery(Action $view, RequestContext $request)
    {
        $view->setLayout(new PresentationLayout());
        $gallery = Criteria::query(Gallery::getClass())->findByKey($request["id"]);
        MediaAPI::prepareMedia(array($gallery));

        $view->assign("canEdit", $this->canEdit($view));

        $view->assign("gallery", $gallery);
    }

    public function gallery_create(IAction $action, RequestContext $request)
    {
        $gal = new Gallery(array("name" => $request["name"], Gallery::F_PAGE_ID => $request["page"]));
        $gal->save();
    }

    public function gallery_remove(IAction $action, RequestContext $request)
    {
        Criteria::query(Gallery::getClass())->findByKey($request["key"])->delete();
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

    public function switch_page_switchEditionState(IAction $action, RequestContext $request)
    {
        if (!isset($_SESSION["org.arrowplatform.package.cms"]["front.page.edition.state"]))
            $_SESSION["org.arrowplatform.package.cms"]["front.page.edition.state"] = false;

        $_SESSION["org.arrowplatform.package.cms"]["front.page.edition.state"] = !$_SESSION["org.arrowplatform.package.cms"]["front.page.edition.state"];
    }


    public function operations_addFileToPage(IAction $action, RequestContext $request)
    {

        //Element::createFromFile(false, )

        $demo_mode = true;
        $upload_dir = './data/uploads/';
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

        if (strtolower($_SERVER['REQUEST_METHOD']) != 'post') {
            exit_status('Error! Wrong HTTP method!');
        }

        if (array_key_exists('pic', $_FILES) && $_FILES['pic']['error'] == 0) {

            $pic = $_FILES['pic'];

            if (!in_array(get_extension($pic['name']), $allowed_ext)) {
                exit_status('Only ' . implode(',', $allowed_ext) . ' files are allowed!');
            }

            if ($demo_mode) {

                // File uploads are ignored. We only log them.

                $line = implode('		', array(date('r'), $_SERVER['REMOTE_ADDR'], $pic['size'], $pic['name']));
                //file_put_contents('log.txt', $line.PHP_EOL, FILE_APPEND);

                exit_status('Uploads are ignored in demo mode.');
            }

            // Move the uploaded file from the temporary
            // directory to the uploads folder:

            if (move_uploaded_file($pic['tmp_name'], $upload_dir . $pic['name'])) {
                exit_status('File was uploaded successfuly!');
            }

        }

        exit_status('Something went wrong with your upload!');

    }


    public function pages_modules_list(Action $view, RequestContext $request, $package)
    {
    }

    public function pages_modules_edit(Action $view, RequestContext $request, $package)
    {
    }

    public function front_page(Action $view, RequestContext $request, $package)
    {
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
            ->simple("id","id")
            ->simple("date","Data")
            ->simple("title","Tytuł")
            ->add(
                Menu::_new("Opcje")
                    ->add(Links::edit())
                    ->add()
                    ->add(Links::delete(News::getClass()))
            );
        $ds = TableDatasource::fromClass( News::getClass());
        $ds->c("type",$request["type"]?$request["type"]:2);
        $ds->c("partner_id",1);
        $ds->c("language",[ "all", $request["lang"]?$request["lang"]:"pl" ], Criteria::C_IN);

        $table = Table::create("news", $ds , $columns)
            ->setToolbar(Toolbar::_new("",[Links::add()]));


        if( $table->getState(Table::STATE_GLOBAL_SEARCH)){
            $ds->addSearchCondition(["id","title","date"],"%".$table->getState(Table::STATE_GLOBAL_SEARCH)."%");
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
                ->addField("Typ", $type, ["cols" =>10])
                ->addField("Nazwa", Text::_new("title"))
                ->addField("Link", Text::_new("link"),[ "display" => $form->getFieldValue($type)==1|| !$form->getFieldValue($type)])
                ->row()
                ->addField("Data", Date::_new("date", "Podaj datę"))
                ->addField("Data zakończenia (opcjonalnie)", Date::_new("date2", "Podaj datę"), [ "display" =>$form->getFieldValue($type)==1 || !$form->getFieldValue($type)])
                ->rowEnd()
                ->addField("Aktywny", SwitchF::_bool("active", 1))
                ->addField("Język", SwitchF::_new("language", array("all" => "Wszystkie", "pl" => "Polski", "en" => "Angielski"), "all"),["cols" =>10])
                //->addField(null,Hidden::_new("partner_id",1))
                //->addField("Plik", File::_new("files") )
                ->addField("Treść krótka", Textarea::_new("content_short")->setBig())
                ->addField("Treść", Textarea::_new("content")->setBig())


            ;

        $view->assign("builder", PopupFormBuilder::_new($form, $fields, News::getClass(), $news)->setTitles("Dodaj wydarzenie", "Edytuj wydarzenie"));

    }

    public function news_save(IAction $action, RequestContext $request)
    {
        $data = $request["data"];
        unset($data["files"]);
        if (empty($data["date2"])) unset($data["date2"]);
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