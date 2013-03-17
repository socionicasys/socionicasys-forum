<?php

$url = $_GET['url'];
$matches = array();
if (preg_match('#^http://(?:www\.)?youtube\.com/watch\?$#', $url) === 1) {
    $v = $_GET['v'];
    if (preg_match('#^http://(?:www\.)?youtube\.com/watch\?.*v=([0-9A-Za-z-_]{11})$#', $url, $matches) === 1)
    {
        $v = $matches[1];
    }
    if (!empty($v))
    {
?>
<iframe title="YouTube video player" class="youtube-player" type="text/html" width="640" height="390" src="http://www.youtube.com/embed/<?php echo $v; ?>?rel=0" frameborder="0"></iframe>
<?php
    }
}
else if (preg_match('#^http://(?:www\.)?vimeo\.com/([0-9]+)$#', $url, $matches) === 1)
{
?>
<iframe src="http://player.vimeo.com/video/<?php echo $matches[1]; ?>?title=0&amp;byline=0&amp;portrait=0" width="640" height="464" frameborder="0"></iframe>
<?php
}
else if (preg_match('#http://vkontakte\.ru/video_ext\.php\?oid=([-0-9]+)#', $url, $matches))
{
	$id = $_GET['id'];
	$hash = trim($_GET['hash'], "\\'");
?>
<iframe src="http://vkontakte.ru/video_ext.php?oid=<?php echo $matches[1]; ?>&amp;id=<?php echo $id; ?>&amp;hash=<?php echo $hash; ?>" width="607" height="360" frameborder="0"></iframe>
<?php
}
else
{
	die();
}
