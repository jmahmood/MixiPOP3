<?php

/*
JCurl 1.0
© Copyright 2010 Jawaad Mahmood

    JCurl is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    JCurl is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with JCurl.  If not, see <http://www.gnu.org/licenses/>.

    http://www.gnu.org/licenses/gpl.txt


My original Website class was the first OOP I ever did seriously.
It suffers from a lot of usability problems among other issues.
This is an attempt to fix those and make it easier to use.

*/


class CurlProfiles{
    static function au(){
        return array("USERAGENT"=>"KDDI-TS3B UP.Browser/6.2.0.12.1.3 (GUI) MMP/2.0", "HEAD"=>"image/bci, application/x-kmcs-form-data, application/x-www-form-urlencoded, application/x-kddi-playlist, application/x-tar, application/vnd.KDDI-vpimlist, application/vnd.KDDI-setsynctime, application/vnd.KDDI-verror, application/vnd.syncml+wbxml, application/x-kddi-drm, text/x-vmessage, text/x-vcard, text/x-vcalendar, text/calendar, text/vcard, application/x-kddi-htmlmail, application/x-kddi-ezmusic, application/x-kddi-karrange, application/x-kcf-license, application/x-kddi-kcf, application/octet-stream,application/vnd.phonecom.mmc-xml,application/vnd.uplanet.bearer-choice-wbxml,application/vnd.wap.wmlc;type=4365,application/vnd.wap.xhtml+xml,application/xhtml+xml;profile=\"http://www.wapforum.org/xhtml\",image/bmp,image/gif,image/jpeg,image/png,image/vnd.wap.wbmp,image/x-up-wpng,multipart/mixed,multipart/related,text/css,text/html,text/plain,text/vnd.wap.wml;type=4365,application/x-shockwave-flash,audio/vnd.qcelp,application/x-smaf,application/vnd.yamaha.hv-script,application/x-mpeg,video/3gpp2,audio/3gpp2,video/3gpp,audio/3gpp,text/x-hdml,*/*");
    }
    
    static function pc(){
        return array("USERAGENT", "Mozilla/5.0 (X11; U; Linux i686; it; rv:1.8.1.5) Gecko/20070713 Firefox/2.0.0.5");
    }
}


class Website{
	var $html, $response, $curl, $type='pc';
        private $fp;
        
        function __construct(){
            $this->curl= curl_init();
        }
        
        function __destruct(){
            curl_close($this->curl);
            if ($this->fp) fclose($this->fp);
        }
        
        // Get a URL while maintaining the state in $curl.
        function get($url, $getvars=false){
	    if (is_array($getvars)){
		throw new Exception("Getvars must be sent as a string or bool.");
	    }
            if ($getvars)
                $url .= "?" . $getvars;

            curl_setopt($this->curl, CURLOPT_URL, $url);
 	    curl_setopt($this->curl, CURLOPT_POST,0);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS,'');
            curl_setopt($this->curl, CURLOPT_BINARYTRANSFER, false);
            $this->set_defaults();
            $this->exec();
        }
        
        // POST to a URL while maintaining state in $curl.
        function post($url, $postvars, $getvars=false){
            if ($getvars){ $url .= "?" . $getvars; }
	    curl_setopt($this->curl, CURLOPT_URL, $url);
 	    curl_setopt($this->curl, CURLOPT_POST,1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS,$postvars);
            curl_setopt($this->curl, CURLOPT_BINARYTRANSFER, false);
            $this->set_defaults();
            $this->exec();
        }

        
        function get_file($url, $getvars=false){
            if ($getvars){ $url .= "?" . $getvars; }
	    curl_setopt($this->curl, CURLOPT_URL, $url);
            curl_setopt($this->curl, CURLOPT_BINARYTRANSFER, true);
 	    curl_setopt($this->curl, CURLOPT_POST,0);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS,'');
            $this->set_defaults();
            curl_setopt($this->curl, CURLOPT_HEADER, false); // Including the header screws up the binary output.
            $this->exec();
        }

        function post_file($url, $post_array, $getvars=false){
            
        }
        
        function set_defaults(){
            $defaults = CurlProfiles::$this->type;

            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->curl, CURLOPT_HEADER, true);
            curl_setopt($this->curl, CURLOPT_USERAGENT, $defaults['USERAGENT']);
        }
        
        private function exec(){
            // execute the curl object and store its information in $html.
	    $output = curl_exec($this->curl);
	    $this->response = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            $this->html = $output;
        }
        
        function html(){
            return $this->html;
        }
        
        // Return HTML Headers receied from the last call.
        function header(){
            
            if (empty($this->html))
                throw new Exception("Can't get header as it doesn't appear that you've made a call yet.");
	    $info = curl_getinfo($this->curl);
            return substr($this->html, 0, $info['header_size']);
        }
        
        function diagnose(){
            if ($this->response > 202)
                throw new Exception("Could not load the website.  Response Code: " . $this->response);
        }
        
        function cookies(){
            // Enables cookies.
            $this->fp = fopen( dirname(__FILE__) . "/cookiejar.txt", "w");

	    curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->fp);
      	    curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->fp);

        }
        
	function encode($old, $new){
	    $this->html = mb_convert_encoding($this->html, $new, $old);
	}

}
?>
