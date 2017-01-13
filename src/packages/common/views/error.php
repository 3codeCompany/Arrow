<div id="subMenu">
    <h3>Wystąpił problem w przetwarzaniu tego zadania</h3>
    <a class="cancel" href="#">Zamknij</a>
</div>
<div>
    <div style="padding: 20px;">
        <img src="r:common::/graphic/administration/error.gif" style="float: left; width: 100px; margin-right: 10px;" />
        <h3><?=$request["message"];?></h3><br/>
        <?
        $params = json_decode($request["parameters"]);
        foreach ($params as $label => $param) {
            if (!is_numeric($label))
                print "<b>{$label} :</b> ";
            print $param . "<br /><br />";
        }
        ?>
    </div>
</div>


