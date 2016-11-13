<?php
    namespace Arrow\Package\Access;

    use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Event\FeatureEvent;
    use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
    use Behat\MinkExtension\Context\MinkContext;
    use Sanpi\Behatch\Context\BehatchContext;
//
// Require 3rd-party libraries here:
//
//   require_once 'PHPUnit/Autoload.php';
//   require_once 'PHPUnit/Framework/Assert/Functions.php';
//

    use Arrow\ORM\Persistent\Criteria,
    Arrow\Package\Access\User,
    Arrow\Package\Access\AccessGroup,
    \Arrow\Package\Access\Auth;

    /**
     * Features context.
     */
class AccessContext extends BehatContext
{

    public  static  $givenAccounts = array();


    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {



    }

    /**
     * @Given /^account "([^"]*)" with password "([^"]*)" in "([^"]*)" group$/
     */
    public function accountWithPasswordInGroup($login, $password, $group)
    {
        /**
         * @var \Arrow\Package\Access\User
         */
        $user = Criteria::query( User::getClass() )
            ->c(User::F_LOGIN, $login)
            ->findFirst();

        if(!empty($user))
            $user->delete();
            
        $group = Criteria::query(AccessGroup::getClass())
            ->c("name", $group)
            ->findFirst();

        /*if(!empty($user)){
            $groups = $user->getAccessGroups();
            if( count($groups) != 1 )
                throw new \Exception("User don't have only one access group");
            if(reset($groups) != $group )
                throw new \Exception("User have wrong access group");
            if($user["active"] == 0)
                throw new \Exception("User not active");
            if(!User::comparePassword($user[User::F_PASSWORD], $password))
                throw new \Exception("Wrong user account password");
        }else*/{
            $user = new User( array(
                User::F_LOGIN => $login,
                User::F_PASSWORD => $password,
                User::F_ACTIVE => 1
            ) );

            $user->save();

            if(!$group)
                throw new \Exception("Access group doesn't exist");
            $user->setGroups(array($group->getPKey()));
        }

        self::$givenAccounts[$login] = $password;

    }

    /**
     * @When /^I am logged as "([^"]*)"$/
     */
    public function iMLoggedAs($login)
    {
        $base = $this->getMainContext()->baseUrl;
        $action = \Arrow\Router::generateLink('operation', 'access::auth/login');
        $url = $base."/".$action."?login=".$login."&password=".self::$givenAccounts[$login];
        //$this->printDebug($action);
        //$this->printDebug($url);
        //new \Behat\Behat\Context\Step\Given('I send a POST request on "'.$url.'"');
        $this->getMainContext()->getSession()->visit( $url );
    }
}