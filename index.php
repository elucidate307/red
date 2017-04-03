<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Get Youtube</title>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
<script type="text/javascript">
var mono = {};
//mono.gsscripthost = 'https://localhost/';
mono.gsscripthost = 'https://green2.herokuapp.com/';
mono.gsscriptfile = 'getcode_fromurl.php';
mono.gsbaseurl = mono.gsscripthost + mono.gsscriptfile;
mono.ytbase = 'https://www.youtube.com/';
mono.ytgvi = 'get_video_info?video_id=';
mono.ytgviurl = mono.ytbase + mono.ytgvi;
mono.proxy = {};
mono.proxy.url = {};
mono.proxy.url.ja = '219.127.253.43:80';
mono.proxy.url.en = '158.69.209.181:8888';

function GetPageSource() {
    clearPage();
    /*
    addScriptTag('getvideoinfo', mono.gsbaseurl +
        '?url=' + encodeURIComponent(mono.ytgviurl + $('#url').val().substr($('#url').val().indexOf('=') + 1)) +
        '&fnc=analyzevideoinfo' +
        '&purl=' + $('#proxyurl').val());
    */
    addScriptTag('srcscript', mono.gsbaseurl +
        '?url=' + encodeURIComponent($('#url').val()) +
        '&fnc=analyzeytplayer' +
        '&purl=' + $('#proxyurl').val());
}

function analyzevideoinfo(info) {
    $('#getVideoInfoArea').val(decodeURIComponent(decodeURIComponent(info)));
}

function analyzeytplayer(src) {
    $('#responseArea').val(src);
    var startchar = 'var ytplayer = ytplayer || {};ytplayer.config =';
    var endchar = 'ytplayer.load = ';
    var html = decodeURIComponent(src);
    $('#fullSrcCodeArea').val(html);
    if (html.indexOf(startchar) === -1) {
        dispErrMsg();
        return;
    }
    $('#errmsg').text('');
    $('#errmsg').css('visibility', 'collapse');
    (function(js) {
        mono.js = js;
        $('#jsArea').val(JSON.stringify(js));
        $('#srcDispArea').val(JSON.stringify(js.args));
        addScriptTag('assets', mono.gsbaseurl +
            '?url=' + encodeURIComponent(mono.ytbase + js.assets.js) +
            '&fnc=analyzebasejs');
    })(JSON.parse(html.slice(html.indexOf(startchar) + startchar.length + 1, html.indexOf(endchar) - 1)));
}

function analyzebasejs(src) {
    var funcname, org = decodeURIComponent(src), str = org;
    while (1) {
        var keyword = 'set("signature",', search = str.indexOf(keyword), s = str.substr(search);
        if (s.substr(0, s.indexOf(')')).indexOf('(') === -1) {
            str = s.slice(search, keyword.length);
            continue;
        }
        funcname = s.slice(keyword.length, s.indexOf(';') - 1);
        funcname = funcname.substr(0, funcname.indexOf('('));
        break;
    }

    var getentitifunc = function(o, f) {
        var entity = o.substr(o.indexOf(f + '='));
        return entity.substr(0, entity.indexOf('};') + 2);
    };
    var funcentity = 'var ' + getentitifunc(org, funcname);
    var subfuncname = funcentity.substr(funcentity.indexOf(';'));
    subfuncname = subfuncname.substr(1, subfuncname.indexOf('.') - 1);
    var subfuncentity = 'var ' + getentitifunc(org, subfuncname);
    var probe_url = decodeURIComponent(mono.js.args.adaptive_fmts);
    var script = $('<script>');
    script.attr('type', 'text/javascript');
    script.html('function getSignature(s){' + subfuncentity + funcentity + 'var result = ' + funcname + '(s); return result; } Analyze();');
    $('head').append(script);
}

function Analyze() {
    if (mono.js.args.adaptive_fmts) {
        Analyze_adaptive_fmts(mono.js.args.adaptive_fmts);
        return;
    }
    if (mono.js.args.url_encoded_fmt_stream_map) {
        Analyze_url_encoded_fmt_stream_map(mono.js.args.url_encoded_fmt_stream_map);
        return;
    }
    Analyze_url_encoded_fmt_stream_map(mono.js.args.url_encoded_fmt_stream_map);
}

function Analyze_adaptive_fmts(urls) {
    if (!urls) {
        dispErrMsg();
        return;
    }
    var exitflg = false;
    while (1) {
        var cidx = urls.indexOf(',');
        var info = unEscapeHTML(decodeURIComponent(cidx !== -1 ? urls.substr(0, cidx) : urls));
        var start = urls.indexOf('url=') + 4;
        if (start === -1) {
            break;
        }
        urls = urls.substr(start);
        if (!urls) {
            break;
        }
        var fileurl = unEscapeHTML(decodeURIComponent(urls.substr(0, urls.indexOf('&'))));
        fileurl = fileurl.indexOf('mime=audio') > -1 ? fileurl.replace('&xtags=', '') : fileurl;
        fileurl = addSignature(fileurl, info);
        if (exitflg) {
            break;
        }
        if (urls.indexOf(',') !== -1) {
            urls = urls.substr(urls.indexOf(',') + 1);
        } else {
            exitflg = true;
        }
        var player, ul, li = $('<li>'), div =$('<div>'), a = $('<a>');
        var type = info.slice(info.indexOf('&type=') + 12, info.indexOf(';'));
        var codecs = info.slice(info.indexOf('codecs=') + 8, info.indexOf('"&'));
        var tmp = info.substr(info.indexOf('bitrate=') + 8);
        var bitrate = Math.round(tmp.slice(0, tmp.indexOf('&')) / 1024);
        a.attr('target', '_blank');
        if (fileurl.indexOf('mime=video') > -1) {
            ul = $('#movielist');
            var tmp = info.substr(info.indexOf('size=') + 5),
            size = tmp.slice(0, tmp.indexOf('&')),
            p = info.slice(info.indexOf('quality_label=') + 14, info.indexOf('p&') + 1);
            a.html(p + '(' + size + ')' + '/' + type + ',' + codecs + '(' + bitrate + 'kbps)');
            player = $('<video>');
            player.attr('width', 320);
            player.attr('height', 240);
            //div.append(player);
        } else if (fileurl.indexOf('mime=audio') > -1) {
            ul = $('#audiolist');
            a.html(type + '/' + codecs + '(' + bitrate + 'kbps)');
            player = $('<audio>');
            //div.append(player);
        } else {
            ul = $('#unknownlist');
            a.html(type + '/' + codecs);
        }
        a.attr('href', fileurl);
        player.attr('src', fileurl);
        player.get(0).controls = true;
        ul.append(li.append(div/*.append($('<br>'))*/.append(a)));
    }
}

function Analyze_url_encoded_fmt_stream_map(urls) {
    if (!urls) {
        dispErrMsg();
        return;
    }
    var exitflg = false;
    while (1) {
        var cidx = urls.indexOf(',');
        var info = unEscapeHTML(decodeURIComponent(cidx !== -1 ? urls.substr(0, cidx) : urls));
        var start = urls.indexOf('url=') + 4;
        if (start === -1) {
            break;
        }
        urls = urls.substr(start);
        if (!urls) {
            break;
        }
        var fileurl = unEscapeHTML(decodeURIComponent(urls.substr(0, urls.indexOf('&'))));
        fileurl = fileurl.indexOf('mime=audio') > -1 ? fileurl.replace('&xtags=', '') : fileurl;
        fileurl = addSignature(fileurl, info);
        if (exitflg) {
            break;
        }
        if (urls.indexOf(',') !== -1) {
            urls = urls.substr(urls.indexOf(',') + 1);
        } else {
            exitflg = true;
        }
        var player, ul, li = $('<li>'), div =$('<div>'), a = $('<a>');
        var type = info.slice(info.indexOf('&type=') + 12, info.indexOf(';'));
        var codecs = info.slice(info.indexOf('codecs=') + 8, info.indexOf('"&'));
        a.attr('target', '_blank');
        if (fileurl.indexOf('mime=video') > -1) {
            ul = $('#movielist');
            a.html(type + ',' + codecs);
            player = $('<video>');
            player.attr('width', 320);
            player.attr('height', 240);
            //div.append(player);
        } else if (fileurl.indexOf('mime=audio') > -1) {
            ul = $('#audiolist');
            a.html(type + '/' + codecs);
            player = $('<audio>');
            //div.append(player);
        } else {
            ul = $('#unknownlist');
            a.html(type + '/' + codecs);
        }
        a.attr('href', fileurl);
        player.attr('src', fileurl);
        player.get(0).controls = true;
        ul.append(li.append(div/*.append($('<br>'))*/.append(a)));
    }
}

function addSignature(fileurl, info) {
    if (fileurl.indexOf('signature') !== -1) {
        return fileurl;
    }
    var sidx = info.indexOf('&s=');
    if (sidx === -1) {
        return fileurl;
    }
    var s = info.substr(sidx + 3);
    s = s.substr(0, s.indexOf('&'));
    fileurl += '&signature=' + getSignature(s);
    return fileurl;
}

function addScriptTag(id, src) {
    setTimeout(function() {
        $('head').append($('<script>').attr('type', 'text/javascript').attr('id', id).attr('src', src));
    }, 0);
}

var unEscapeHTML = function (str) {
    return str.replace(/(&lt;)/g, '<').replace(/(&gt;)/g, '>').replace(/(&quot;)/g, '"').replace(/(&#39;)/g, "'").replace(/(&amp;)/g, '&');
};

function clearPage() {
    $('#movielist').html('');
    $('#audiolist').html('');
    $('#unknownlist').html('');
    if ($('#srcscript')) {
        $('#srcscript').remove();
    }
    $('#fullSrcCodeArea').val('');
    $('#jsArea').val('');
    $('#getVideoInfoArea').val('');
    $('#srcDispArea').val('');
}

function dispErrMsg() {
    $('#errmsg').text('Invalid URL!!');
    $('#errmsg').css('visibility', 'visible').css('color', 'red').css('font-size', '30px');
}

</script>
</head>
<body>
<div id="errmsg"></div>
<select id="country">
    <option value="unuse">Unuse</option>
    <option value="ja">日本</option>
    <option value="en">USA</option>
</select>
<input type="text" id="proxyurl" value="" style="width: 285px"></input>
<br>
<input type="text" id="url" value="https://www.youtube.com/watch?v=Y7aEiVwBAdk" style="width: 300px"></input>
<button onClick="GetPageSource()">Get</button>
<br>
<div id="urlarea"></div>
<h3>Movie</h3>
<ul id="movielist">
</ul>
<h3>Audio</h3>
<ul id="audiolist">
</ul>
<h3>Unknown</h3>
<ul id="unknownlist">
</ul>
<table style="display: none;">
    <tr>
        <td>source code:</td>
        <td>url_encoded_fmt_stream_map:</td>
        <td>js:</td>
        <!-- <td>get_video_info:</td> -->
    </tr>
    <tr>
        <td><textarea id="fullSrcCodeArea" style="width:300px; height:100px;"></textarea></td>
        <td><textarea id="srcDispArea" style="width:300px; height:100px;"></textarea></td>
        <td><textarea id="jsArea" style="width:300px; height:100px;"></textarea></td>
        <!-- <td><textarea id="getVideoInfoArea" style="width:300px; height:100px;"></textarea></td> -->
    </tr>
</table>
<script type="text/javascript">
$('#url').keypress(function(e) {
    if (e.which == 13) {
        GetPageSource();
    }
});
$(function($) {
    $('#country').change(function() {
        switch ($(this).val())
        {
            case 'unuse':
                $('#proxyurl').val('');
                break;
            case 'en':
                $('#proxyurl').val(mono.proxy.url.en);
                break;
            case 'ja':
            default:
                $('#proxyurl').val(mono.proxy.url.ja);
                break;
        }
    });
});
</script>
</body>
</html>
