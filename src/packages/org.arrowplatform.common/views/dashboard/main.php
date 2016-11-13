<? $this->assign('user', \Arrow\Package\Access\Auth::getDefault()->getUser())?>

<a href="<link:template path="access::users/account" />" class="module" style="background-color:#008D15" >
    <img src="<link:resource path="common::/graphic/dashboard/Finder.png" />" />
    <div style="float: left;">
        <b>Zalogowany jako: </b> <?=$this['user']['login']?><br />
        <?
        $r = \Arrow\ORM\Persistent\Criteria::query(\Arrow\Package\Common\Track::getClass())
            ->c(\Arrow\Package\Common\Track::F_CLASS, \Arrow\Package\Access\User::getClass())
            ->c(\Arrow\Package\Common\Track::F_OBJECT_ID, $this["user"]["id"])
            ->c(\Arrow\Package\Common\Track::F_ACTION, "login")
            ->limit(0,10)
            ->order("id", \Arrow\ORM\Persistent\Criteria::O_DESC)
            ->findFirst();
        $info = unserialize($r["info"]);
        ?>
        <b>Ostatnie logowanie:</b><br /> <?  print $r["date"] . " IP: {$info['IP']} " . ($info["result"] ? 'Udane' : 'Nie udane') . " <br />"; ?>

        <b>Dzisiejsza data: </b> <?=date("Y-m-d")?>
    </div>
</a>