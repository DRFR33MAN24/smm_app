<?php

namespace App\Helper;
/**

 *	@author Dhru.com

 *	@APi kit version 2.0 March 01, 2012

 *	@Copyleft GPL 2001-2011, Dhru.com

 **/

if (!extension_loaded('curl'))
{
    trigger_error('cURL extension not installed', E_USER_ERROR);
}
class DhruFusion
{
    var $xmlData;
    var $xmlResult;
    var $debug;
    var $action;
    var $username;
    var $apiaccesskey;
    var $url;
    function __construct(string $username,string $apiaccesskey, string $url)
    {
        $this->xmlData = new \DOMDocument();
        $this->username = $username;
        $this->apiaccesskey = $apiaccesskey;
        $this->url = $url;
    }
    function getResult()
    {
        return $this->xmlResult;
    }
    function action($action, $arr = array())
    {
        if (is_string($action))
        {
            if (is_array($arr))
            {
                if (count($arr))
                {
                    $request = $this->xmlData->createElement("PARAMETERS");
                    $this->xmlData->appendChild($request);
                    foreach ($arr as $key => $val)
                    {
                        $key = strtoupper($key);
                        $request->appendChild($this->xmlData->createElement($key, $val));
                    }
                }
                $posted = array(
                    'username' => $this->username,
                    'apiaccesskey' => $this->apiaccesskey,
                    'action' => $action,
                    'requestformat' => "JSON",
                    'parameters' => $this->xmlData->saveHTML());
                $crul = curl_init();
                curl_setopt($crul, CURLOPT_HEADER, false);
                curl_setopt($crul, CURLOPT_USERAGENT, 'PostmanRuntime/7.37.0');
                curl_setopt($crul, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($crul, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($crul, CURLOPT_URL, $this->url.'/api/index.php');
                curl_setopt($crul, CURLOPT_POST, true);
                curl_setopt($crul, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($crul, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($crul,   CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1);
                curl_setopt($crul,   CURLOPT_MAXREDIRS , 10);
                curl_setopt($crul,   CURLOPT_TIMEOUT , 0);
               
                curl_setopt($crul,   CURLOPT_ACCEPT_ENCODING , 'gzip, deflate');
               curl_setopt($crul,CURLOPT_COOKIE,"DHRUFUSION=f6c05169ff0d81f673d56ddfbb978a64");
                curl_setopt($crul,   CURLOPT_CUSTOMREQUEST , 'POST');
                curl_setopt($crul,   CURLOPT_HTTPHEADER , [
                    'Content-Type: multipart/form-data; ' ]);
                curl_setopt($crul, CURLOPT_POSTFIELDS, $posted);
                $response = curl_exec($crul);
                if (curl_errno($crul) != CURLE_OK)
                {
                    echo curl_error($crul);
                    curl_close($crul);
                }
                else
                {
                    curl_close($crul);
                    // $response = XMLtoARRAY(trim($response));
                    if ($this->debug)
                    {
                        echo "<textarea rows='20' cols='200'> ";
                        print_r($response);
                        echo "</textarea>";
                    }
                    return (json_decode($response, true));
                }
            }
        }
        return false;
    }
}
function XMLtoARRAY($rawxml)
{
    $xml_parser = xml_parser_create();
    xml_parse_into_struct($xml_parser, $rawxml, $vals, $index);
    xml_parser_free($xml_parser);
    $params = array();
    $level = array();
    $alreadyused = array();
    $x = 0;
    foreach ($vals as $xml_elem)
    {
        if ($xml_elem['type'] == 'open')
        {
            if (in_array($xml_elem['tag'], $alreadyused))
            {
                ++$x;
                $xml_elem['tag'] = $xml_elem['tag'].$x;
            }
            $level[$xml_elem['level']] = $xml_elem['tag'];
            $alreadyused[] = $xml_elem['tag'];
        }
        if ($xml_elem['type'] == 'complete')
        {
            $start_level = 1;
            $php_stmt = '$params';
            while ($start_level < $xml_elem['level'])
            {
                $php_stmt .= '[$level['.$start_level.']]';
                ++$start_level;
            }
            $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
            eval($php_stmt);
            continue;
        }
    }
    return $params;
}

?>



