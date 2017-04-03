<?php

if (!isset($_GET['url']))
{
    echo 'URL is not set';
    return;
}

if (!isset($_GET['fnc']))
{
    echo 'Function is not set';
    return;
}

$url = rawurldecode($_GET['url']);
$fnc = $_GET['fnc'];

try
{
    $purl = isset($_GET['purl']) ? $_GET['purl'] : '';
    if (!empty($purl))
    {
        $proxy = array('http' => array('proxy' => $purl, 'request_fulluri' => true,),);
        $proxy_context = stream_context_create($proxy);
        $html = @file_get_contents($url, false, $proxy_context);
    }
    else
    {
        $html = @file_get_contents($url);
    }
}
catch (Exception $e)
{
    echo $e;
    return;
}

$html = rawurlencode($html);
echo 'var param ="' . $html . '";';
echo $fnc . '(param);';
