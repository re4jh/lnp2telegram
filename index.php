<?php
header("Content-Type: text/plain; charset=utf-8");

function sendTelegramMessage($sMessage, $sChatId, $sToken)
{
    $url = "https://api.telegram.org/bot" . $sToken . "/sendMessage?chat_id=" . $sChatId;
    $url = $url . "&text=" . urlencode($sMessage);
    $ch = curl_init();
    $optArray = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true
    );
    curl_setopt_array($ch, $optArray);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

$file_cache = 'lastversion.xml';
$url = "https://feeds.metaebene.me/lnp/m4a";
$fc_old = file_get_contents($file_cache);

// get the feed as by curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$result = curl_exec($ch);
$iHttpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($iHttpStatus == 200) {
    echo '-- Download of ' . $url . ' successful' . "\n";
} else {
    die ('-- Download of ' . $url . ' could not be completed. http-status: ' . $iHttpStatus . "\n");
}


if ($result == $fc_old) {
    echo '-- Same content in Cache and in Feed-URL. I quit.' . "\n";
} else {
    echo '-- I found some differences!' . "\n";

    $xml_data_old = new DOMDocument();
    $xml_data_old->loadXML($fc_old);
    $xpath_old = new DOMXPath($xml_data_old);

    $objLatestItem_old = $xpath_old->query('//rss/channel/item');
    $sLatestTitle_old = $xpath_old->query("title", $objLatestItem_old[0])->item(0)->nodeValue;
    $sLatestLink_old = $xpath_old->query("link", $objLatestItem_old[0])->item(0)->nodeValue;
    echo '-- Latest known title in Cache: "' . $sLatestTitle_old . "\"\n";
    echo '-- Latest known link in Cache: "' . $sLatestLink_old . "\"\n";

    $xml_data_new = new DOMDocument();
    $xml_data_new->loadXML($result);
    $xpath_new = new DOMXPath($xml_data_new);

    $objLatestItem_new = $xpath_new->query('//rss/channel/item');
    $sLatestTitle_new = $xpath_new->query("title", $objLatestItem_new[0])->item(0)->nodeValue;
    $sLatestLink_new = $xpath_new->query("link", $objLatestItem_new[0])->item(0)->nodeValue;
    echo '-- Latest known title online: "' . $sLatestTitle_new . "\"\n";
    echo '-- Latest known link online: "' . $sLatestLink_new . "\"\n";

    if ($sLatestTitle_old == $sLatestTitle_new && $sLatestLink_old == $sLatestLink_new) {
        echo '-- Latest title and latest link match. I quit.';
        file_put_contents($file_cache, $result);
    } else {
        echo '-- there seems to be something new. i am going to tell that on telegram';
        $sMessage = 'Guck mal, da gibts was Neues: "' . $sLatestTitle_new . '"' . "\n";
        $sMessage .= "\n" . 'URL: ' . $sLatestLink_new . "\n";
        sendTelegramMessage($sMessage, '###the-chat-id-to-post-to###', '###get-your-own-key-from-botfather###');
        file_put_contents($file_cache, $result);
    }
}



