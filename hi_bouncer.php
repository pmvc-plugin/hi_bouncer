<?php
namespace PMVC\PlugIn\hi_bouncer;
use PMVC\PlugIn\curl\CurlHelper;
use date;
${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\hi_bouncer';

/**
 * @config user
 * @config pass
 * @config type 
 */
class hi_bouncer extends \PMVC\PlugIn
{
    function login($cookie_file)
    {
        $data = $this->getData();
        $curl = new CurlHelper();
        $url = $data['URL'];
        $post_params = [];
        
        foreach($data['PARAMS'] as $param){
            $param = (array)json_decode($param);
            $param[1] = str_replace(
                ['[USER]','[PASS]'],
                [$this['user'], $this['pass']],
                $param[1]
            );
            $post_params[$param[0]] = $param[1];
        }

        $options = [
            CURLOPT_COOKIEJAR=>$cookie_file,
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>http_build_query($post_params, '', '&')
        ];
        $curl->setOptions($url, null, $options);
        $curl->process();
        $return = [
            CURLOPT_COOKIEFILE => $cookie_file
        ];
        return $return;
    }

    function getData()
    {
        $datas = \PMVC\plug('dotenv')
            ->getUnderscoreToArray('.env.hi_bouncer');
        $data = $datas[$this['type']];
        return $data;
    }

    function getNewCookieName()
    {
        if (!isset($this['user'])) {
            $this['user'] = \PMVC\getOption('USER');
        }
        $date = date('Ymd');
        $cookie_file = '/tmp/cookie_'.
            $this['user'].'_'.
            $date;
        return $cookie_file;
    }

    function getCookieFile()
    {
        $cookie_file = $this->getNewCookieName();
        if (!is_file($cookie_file)) {
            $this->login($cookie_file);
        }
        $option = [
            CURLOPT_COOKIEFILE => $cookie_file
        ];
        return $option;
    }

    function __call($method, $args)
    {
        $curl = \PMVC\plug('curl');
        $func = array($curl, $method); 
        if (is_callable($func)) {
            $curlObject = call_user_func_array(
                $func,
                $args
            );
            if (is_object($curlObject) && 
                false!==strpos(
                   get_class($curlObject),
                   'CurlHelper'
                )) {
                $option = $this->getCookieFile();
                $curlObject->set($option);
            }
            return $curlObject;
        }
    }
}
