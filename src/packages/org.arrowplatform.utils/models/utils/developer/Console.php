<?php
namespace Arrow\Package\Utils;
use Arrow\Models\Project;
class Console {

    private static $file;

    public static function init(){
        self::$file = Project::getInstance()->getPath() . "/data/last_dev_console.txt";
    }


    //print h1
    public static function h1($val){
        print "<h2>".$val."</h2>";
        ob_implicit_flush(1);
    }

    //print h2
    public static function h2($val){
        print "<h2>".$val."</h2>";
        ob_implicit_flush(1);
    }

    //print error
    public static function error($val){
        print "<h3 style=\"background-color:red;color:white;\">".$val."</h3>";
        ob_implicit_flush(1);
    }


    // print line
    public static function pl($val){
        print $val."\n";
        for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
        ob_implicit_flush(1);
    }

    // print object
    public static function po($val){
        self::pt(array($val));
    }

    public static function ta($q){
        $res = self::query($q);
        $arr = array();
        while($row = $res->fetch(\PDO::FETCH_ASSOC))
            $arr[] = $row;
        return $arr;
    }

    //print table
    public static function pt($result){
        if(empty($result)){
            print "--empty table--\n";
            return;
        }


        if($result[0] instanceof OrmPersistent){
            $header = $result[0]->getFieldnames();
        }elseif(is_array($result)){
            $header = array_keys($result[0]);
        }elseif(is_string($result)){
            $arr = self::ta($result);
            self::pt($arr);
            return $arr;
        }

        print "<table>";
        print "<thead><tr>";
        foreach($header as $head) print "<th>".$head."</th>";
        print "</tr></thead>";

        print "<tbody>";
        foreach($result as $row){
            print "<tr>";
            foreach($header as $head){
                print "<td>".$row[$head]."</td>";
            }
            print "</tr>";
        }
        print "</tbody>";
        print "</table>\n";
        ob_implicit_flush(1);
        return $result;

    }

    public static function help(){
        $reflector = new ReflectionClass(__CLASS__);

        foreach($reflector->getMethods() as $method){
            if($method->isStatic() ){
                $str = $method->getName()." ( ";



                foreach($method->getParameters() as $parameter){
                    $str.= '$'.$parameter->getName().", ";

                }
                $str = trim($str, ', ');

                $str.= " );";



                self::h2($str);
            }
        }
    }

    public static function query($q){
        $res = Project::getInstance()->getDB()->query($q);
        return $res;
    }


    public static function parseCSVFile($file, $separator){
        $arr = file($file);
        $tmp = array();

        foreach($arr as $row){
            if( !empty($row) )
                $tmp[] = explode($separator, $row);
        }

        $tmp[count($tmp)-1] = str_replace( array( "\r\n", "\n"), "",  $tmp[count($tmp)-1]);




        return $tmp;
    }

    public static function execute(){
        set_time_limit(300);
        //@apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);



        $content = file_get_contents(self::$file);
        $compileFile = ARROW_CACHE_PATH."/dev_console_code.php";
        file_put_contents( $compileFile, "<?php\n".$content."\n?>" );


        print "<div align=\"center\"><b>Processing;</b></div><hr>";
        for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
        ob_implicit_flush(1);
//		try{
        require_once $compileFile;
//		}catch(Exception $ex){
        //print self::error($ex->getMessage());

//		}
        for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
        ob_implicit_flush(1);

        print "<hr><div align=\"center\"><b>End of processing;</b></div>";
        for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
        ob_implicit_flush(1);

        ob_start();
        return "";
    }

}
?>