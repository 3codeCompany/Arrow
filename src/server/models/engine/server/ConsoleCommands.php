<?php
namespace Arrow\Models;


/**
 * Created by JetBrains PhpStorm.
 * User: artur
 * Date: 19.12.12
 * Time: 16:02
 * To change this template use File | Settings | File Templates.
 */
class ConsoleCommands
{

    public static function  process()
    {
        print "Arrowplatform server".PHP_EOL;

        $options = getopt("a:");
        switch($options["a"]){
            case 'createProject':

                print "Creating project ".PHP_EOL;
                self::createProject();
            break;
            default:
                print 'command not recognized';
        }
    }



    private static function createProject(){
        $shortopts  = "a:";

        $longopts  = array(
            "path:" => array( "Application path", true),    // Required value
            "id:" => array( "Application id", true),     // Required value
            "name:" => array( "Application name", false),    // Optional value
            "documentsroot:" => array( "Server documents root", true),        // No value
            "dsn:" => array( "Data source name", true),
            "dsuser:" => array( "Data source user", true),
            "dspass:" => array( "Data source password", true),
        );
        $options = getopt($shortopts, array_keys( $longopts ));

        foreach( $longopts as $key => $data ){
            if($data[1]  && !isset($options[str_replace(":","",$key)])){
                print "Specify ". $data[0]." [".str_replace(":","",$key)."]";
                exit();
            }

        }


        try{
            $pdo = new \PDO($options["dsn"], $options["dsuser"], $options["dspass"]);

            $generator = new ProjectGenerator($options["id"], (isset($options["name"]))?$options["name"]:$options["id"], $options["path"], $options["documentsroot"]);
            $generator->setSetting("db.dsn", $options["dsn"]);
            $generator->setSetting("db.user", $options["dsuser"]);
            $generator->setSetting("db.password", $options["dspass"]);
            $generator->generate();

        }catch(\PDOException $ex){
            print "Problems with database: ".$ex->getMessage();
        }

    }

    private static function ask( $query ){
        print $query." ";
        ob_flush();
        $line = trim(fgets(STDIN));
        return $line;
    }

}