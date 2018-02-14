<?php
/**
 * Created by PhpStorm.
 * User: artur
 * Date: 13.02.2018
 * Time: 21:55
 */

namespace Arrow\Models\Commands\Generators;

use Arrow\Common\Models\Helpers\TableListORMHelper;
use Arrow\Common\Models\Helpers\Validator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CRUDControllerGenerator
{
    protected $namespace;
    protected $className;
    protected $model;
    protected $modelClassName;
    protected $bundleBaseNamespace;

    protected $genNamespace;
    /**
     * @var ClassType
     */
    protected $genClass;

    public function __construct($qualifiedName, $model, $bundleBaseNamespace = "")
    {
        $exploded = explode("\\", $qualifiedName);

        $this->className = array_pop($exploded);
        $this->namespace = implode("\\", $exploded);


        $exploded = explode("\\", $model);
        $this->modelClassName = array_pop($exploded);
        $this->model = $model;

        $this->bundleBaseNamespace = $bundleBaseNamespace;

    }

    public function generate()
    {
        $namespaceObj = new PhpNamespace($this->namespace);
        $namespaceObj->addUse(Route::class);
        $namespaceObj->addUse($this->model);
        $namespaceObj->addUse(Request::class);
        $namespaceObj->addUse(Validator::class);
        $namespaceObj->addUse(TableListORMHelper::class);

        $class = $namespaceObj->addClass($this->className);

        $nameWithoutControllerPostfix = str_replace("Controller", "", $this->className);
        $route = strtolower(
            str_replace($this->bundleBaseNamespace, "", $this->namespace . "/" . $nameWithoutControllerPostfix)
        );
        $class
            ->addComment("Auto-generated Arrow controller")
            ->addComment(
                $this->annotationGeneratorRoute(
                    "/" . $route
                )
            );

        $this->genClass = $class;

        $this->methodGeneratorIndex();
        $this->methodGeneratorAsyncIndex();

        $this->methodGeneratorShow();

        $this->methodGeneratorCreate();
        $this->methodGeneratorStore();

        $this->methodGeneratorEdit();
        $this->methodGeneratorUpdate();

        $this->methodGeneratorDestroy();

        // to generate PHP code simply cast to string or use echo:
        return $namespaceObj;

        //index
        //show
        //create
        //store
        //edit
        //update
        //destroy
    }

    protected function annotationGeneratorRoute(string $route): string
    {
        return '@Route( "' . $route . '" )';
    }

    protected function methodGeneratorIndex()
    {
        $method = $this->genClass->addMethod("index");
        $method
            ->addComment($this->annotationGeneratorRoute(""))
            ->setBody('return [];');
    }

    protected function methodGeneratorAsyncIndex()
    {
        $method = $this->genClass->addMethod("asyncListData");
        $method
            ->addComment($this->annotationGeneratorRoute("/asyncIndex"))
            ->setBody('
$criteria = ' . $this->modelClassName . '::get();

$helper = new TableListORMHelper();

return $helper->getListData($criteria);
');
    }

    protected function methodGeneratorShow()
    {
        $method = $this->genClass->addMethod("show");

        $body = '
$object = ' . $this->modelClassName . '::get() 
    ->findByKey( $key ); 
    
return [
    "object" => $object
];
        ';

        $method
            ->addComment($this->annotationGeneratorRoute("/{key}"))
            ->setBody($body);

        $method
            ->addParameter("key")
            ->setTypeHint("int");
    }

    protected function methodGeneratorCreate()
    {
        $method = $this->genClass->addMethod("create");
        $method
            ->addComment($this->annotationGeneratorRoute("/create"))
            ->setBody('return [];');
    }

    protected function methodGeneratorStore()
    {
        $method = $this->genClass->addMethod("store");

        $body = '
$data = $request->get("data");        

$validator = Validator::create($data)
    ->required([])
;

if($validator->fails()){
    return $validator->response();
}else{        
        
    $object = ' . $this->modelClassName . '::create( $data );
    $object
        ->__filesInitConnection()
        ->__filesAttachUploaded("data.files"); 
        
    return [
        "id" => $object->_id()
    ];
    
}
        ';

        $method
            ->addComment($this->annotationGeneratorRoute("/store"))
            ->setBody($body);

        $method
            ->addParameter("request")
            ->setTypeHint(Request::class);
    }


    protected function methodGeneratorEdit()
    {
        $method = $this->genClass->addMethod("edit");

        $body = '
$object = ' . $this->modelClassName . '::get() 
    ->findByKey( $key ); 
    
return [
    "object" => $object
];
        ';

        $method
            ->addComment($this->annotationGeneratorRoute("/{key}/edit"))
            ->setBody($body);

        $method
            ->addParameter("key")
            ->setTypeHint("int");
    }


    protected function methodGeneratorUpdate()
    {
        $method = $this->genClass->addMethod("update")
            ->addComment($this->annotationGeneratorRoute("/{key}/update"));

        $method
            ->addParameter("key")
            ->setTypeHint("int");

        $method
            ->addParameter("request")
            ->setTypeHint(Request::class);

        $body = '
$data = $request->get("data");        

$validator = Validator::create($data)
    ->required([])
;

if($validator->fails()){
    return $validator->response();
}else{        
        
    $object = ' . $this->modelClassName . '::get() 
        ->findByKey( $key )
        ->__filesInitConnection()
        ->__filesAttachUploaded("data.files")
        ->setValues($data)
        ->save()
        ;  
        
    return [
        "id" => $object->_id()
    ];
}
        ';

        $method
            ->setBody($body);


    }

    protected function methodGeneratorDestroy()
    {
        $method = $this->genClass->addMethod("destroy");

        $body = '
$object = ' . $this->modelClassName . '::get() 
    ->findByKey( $key );
$object->delete();     
    
return [
    true
];
        ';

        $method
            ->addComment($this->annotationGeneratorRoute("/{key}/destroy"))
            ->setBody($body);

        $method
            ->addParameter("key")
            ->setTypeHint("int");
    }
}