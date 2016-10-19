<? /** @var \Arrow\ViewManager $this */ ?>
<div id="subMenu">
    <h3>Log <?=$this["filename"]?></h3>

    <a class="save exit" href="#">Zamknij</a>
</div>
<div>
    <div style="padding: 10px;;">
        <pre>
        <?=$this["content"]?>
        </pre>
    </div>
</div>