<?php
/*
Plugin Name: Xmail - The Right Way
Description: Send email the right way so it does not get flagged as SPAM. Most servers use a diffrent IP address to send email from then the IP of your domain and thus your emails get into SPAM folders or not att all in some cases of Yahoo! and MSN. This will send emails from your domain IP address. It might take 1-2 seconds more to send it but it is worth it.
Version: 1.10
Author: Vlad-Marian MARIAN
Author URI: http://www.transilvlad.com/
License: GPL v.2

             GNU
   GENERAL PUBLIC LICENSE
    Version 2, June 1991

 Copyright(C) 2014 transilvlad.com

*/

  # DEBUG MODE
#  error_reporting(E_ALL & ~E_NOTICE);

  # WP version check
  global $wp_version;
  $exit_msg="Plugin requires WordPress 3.0 or newer.<a href=\"http://wordpress.org/\" target=\"_blank\">Please update!</a>";
  if(version_compare($wp_version,"3.0","<"))
   exit($exit_msg);

  class XmailCore {

    # LOG VAR
    public $log = Array();

    # NEW LINE
    public $line = PHP_EOL;

    # ATTACHED FILES
    public $files = Array();

    # CONFIG SOCKET
    private $from = "xmail@localhost"; // sender email address
    private $host = "localhost"; // your domain name here
    private $port = "25"; // it is always 25 but i think it's best to have this for tests when developper pc has port 25 blocked and server has alternate port [i use 26 cause 25 is locked for anti SPAM by ISP]
    private $time = "30"; // timeout [time short :D]

    function setFrom($value) { if($value != "") $this->from = $value; }
    function setHost($value) { if($value != "") $this->host = $value; }
    function setPort($value) { if($value != "") $this->port = $value; }
    function setTime($value) { if($value != "") $this->time = $value; }

    # MAIN FUNCTION
    function mail($to, $subject, $msg, $headers, $attachments = NULL) {
      # MESSAGE HTML
      $msg = str_replace("\'","'",$msg);
      $msg = str_replace('\"','"',$msg);

      $boundary1 = "-----=" . md5(uniqid(rand()));
      $boundary2 = "-----=" . md5(uniqid(rand()));

      $message .= $this->line . "This is a multi-part message in MIME format." . $this->line . $this->line;
      $message .= "--".$boundary1 . $this->line;
      $message .= "Content-Type: multipart/alternative;" . $this->line . "\tboundary=\"$boundary2\"" . $this->line . $this->line . $this->line;

      # MESSAGE TEXT
      $message .= "--".$boundary2 . $this->line;
      $message .= "Content-Type: text/plain;" . $this->line . "\tcharset=\"us-ascii\"" . $this->line;
      $message .= "Content-Transfer-Encoding: 7bit" . $this->line . $this->line;
      $message .= strip_tags($msg) . $this->line;
      $message .= $this->line . $this->line;

      # MESSAGE HTML
      $message .= "--".$boundary2 . $this->line;
      $message .= "Content-Type: text/html;" . $this->line . "\tcharset=\"us-ascii\"" . $this->line;
      $message .= "Content-Transfer-Encoding: quoted-printable" . $this->line . $this->line;
      $message .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">" . $this->line;
      $message .= "<html>" . $this->line;
      $message .= "<body>" . $this->line;
      $message .= $msg . "<br/>" . $this->line;
      $message .= "</body>" . $this->line;
      $message .= "</html>" . $this->line . $this->line;
      $message .= "--".$boundary2."--" . $this->line . $this->line;

      if(is_array($attachments)) {
        foreach($attachments AS $file_url) {
          if(is_file($file_url)) {
            $file_name = pathinfo($file_url, PATHINFO_BASENAME);
            $file_type = $this->find_mime(pathinfo($file_url, PATHINFO_EXTENSION));

            # ATTACHMENT
            $message .= "--".$boundary1 . $this->line;
            $message .= "Content-Type: ".$file_type.";" . $this->line . "\tname=\"$file_name\"" . $this->line;
            $message .= "Content-Transfer-Encoding: base64" . $this->line;
            $message .= "Content-Disposition: attachment;" . $this->line . "\tfilename=\"$file_name\"" . $this->line;

            $fp = fopen($file_url, "r");
            do {
              $data = fread($fp, 8192);
              if(strlen($data) == 0) break;
              $content .= $data;
            }
            while(true);
            $content_encode = chunk_split(base64_encode($content));
            $message .= $content_encode . $this->line . $this->line;
            $content = "";
            unset($content);

          }
        }
      }
      $message .= "--".$boundary1."--" . $this->line . $this->line;

      $headers .= "X-Mailer: Xmail 1.1" . $this->line;
      $headers .= "MIME-Version: 1.0" . $this->line;
      $headers .= "Content-Type: multipart/mixed;" . $this->line . "\tboundary=\"$boundary1\"" . $this->line;

      if($this->sokmail($to, $subject, $message, $headers)) return true;
      else if(mail($to, $subject, $message, $headers)) return true;
      else return false;
    }

    # send mail directly to destination MX server
    function sokmail($to, $subject, $message, $headers) {
      list($user, $domain) = split("@",$to);
      getmxrr($domain, $mxhosts);
      $server = $mxhosts["0"];

      # open socket
      $socket = @fsockopen($server, $this->port, $errno, $errstr, $this->time);
      if(empty($socket)) return false;
      if($this->parse_response($socket, 220, "SOCKET") != 220) { fclose($socket); return false; }

      # say HELO to our little friend
      fputs($socket, "EHLO " . $this->host . $this->line);
      if($this->parse_response($socket, 250, "HELO") != 250) { fclose($socket); return false; }

      # email from
      fputs($socket, "MAIL FROM: <" . $this->from . ">" . $this->line);
      if($this->parse_response($socket, 250, "MAIL FROM") != 250) { fclose($socket); return false; }

      # email to
      fputs($socket, "RCPT TO: <" . $to . ">" . $this->line);
      if($this->parse_response($socket, 250, "RCPT TO") != 250) { fclose($socket); return false; }

			# send data start command
			fputs($socket, "DATA" . $this->line);
			if($this->parse_response($socket, 354, "DATA") != 354) { fclose($socket); return false; }

			# make the deposit :)
			fputs($socket, "Subject: " . $subject . $this->line);
			fputs($socket, "To: " . $to . $this->line);
			fputs($socket, $headers . $this->line);
			fputs($socket, $message . $this->line);
			fputs($socket, "." . $this->line); # this line sends a dot to mark the end of message
			if($this->parse_response($socket, 250, ".") != 250) { fclose($socket); return false; }

      # say goodbye
      fputs($socket,"QUIT" . $this->line);
      $this->parse_response($socket, 221, "QUIT");
      fclose($socket);

      return true;
    }

    # parse server responces for above function
    function parse_response($socket, $expected, $cmd) {
      $response = "";
      $this->log[$cmd] = "";
      while(substr($response, 3, 1) != " ") {
        if(!($response = fgets($socket, 256))) $this->log["ERROR RESPONSE"] = "Couldn't get mail server response codes.";
        else $this->log[$cmd] .= $response;
        # for security we break the loop after 10 cause this should not happen ever
        $i++;
        if($i == 10) return false;
      }

      # shows an error if expected code not received
      if(substr($response, 0, 3) != $expected) $this->log["ERROR CODES"] = "Ran into problems sending Mail. Received: " . substr($response, 0, 3) . ".. but expected: " . $expected;

      # access denied..quit
      if(substr($response, 0, 3) == 451) $this->log["ERROR QUIT"] = "Server declined access. Quitting.";

      return substr($response, 0, 3);
    }

    function find_mime($ext) {
      # create mimetypes array
      $mimetypes = $this->mime_array();

      # return mime type for extension
      if(isset($mimetypes[$ext]))
       return $mimetypes[$ext];

      # if the extension wasn't found return octet-stream
      else
       return "application/octet-stream";
    }
    function mime_array() {
      return array(
        "ez" => "application/andrew-inset",
        "hqx" => "application/mac-binhex40",
        "cpt" => "application/mac-compactpro",
        "doc" => "application/msword",
        "bin" => "application/octet-stream",
        "dms" => "application/octet-stream",
        "lha" => "application/octet-stream",
        "lzh" => "application/octet-stream",
        "exe" => "application/octet-stream",
        "class" => "application/octet-stream",
        "so" => "application/octet-stream",
        "dll" => "application/octet-stream",
        "oda" => "application/oda",
        "pdf" => "application/pdf",
        "ai" => "application/postscript",
        "eps" => "application/postscript",
        "ps" => "application/postscript",
        "smi" => "application/smil",
        "smil" => "application/smil",
        "wbxml" => "application/vnd.wap.wbxml",
        "wmlc" => "application/vnd.wap.wmlc",
        "wmlsc" => "application/vnd.wap.wmlscriptc",
        "bcpio" => "application/x-bcpio",
        "vcd" => "application/x-cdlink",
        "pgn" => "application/x-chess-pgn",
        "cpio" => "application/x-cpio",
        "csh" => "application/x-csh",
        "dcr" => "application/x-director",
        "dir" => "application/x-director",
        "dxr" => "application/x-director",
        "dvi" => "application/x-dvi",
        "spl" => "application/x-futuresplash",
        "gtar" => "application/x-gtar",
        "hdf" => "application/x-hdf",
        "js" => "application/x-javascript",
        "skp" => "application/x-koan",
        "skd" => "application/x-koan",
        "skt" => "application/x-koan",
        "skm" => "application/x-koan",
        "latex" => "application/x-latex",
        "nc" => "application/x-netcdf",
        "cdf" => "application/x-netcdf",
        "sh" => "application/x-sh",
        "shar" => "application/x-shar",
        "swf" => "application/x-shockwave-flash",
        "sit" => "application/x-stuffit",
        "sv4cpio" => "application/x-sv4cpio",
        "sv4crc" => "application/x-sv4crc",
        "tar" => "application/x-tar",
        "tcl" => "application/x-tcl",
        "tex" => "application/x-tex",
        "texinfo" => "application/x-texinfo",
        "texi" => "application/x-texinfo",
        "t" => "application/x-troff",
        "tr" => "application/x-troff",
        "roff" => "application/x-troff",
        "man" => "application/x-troff-man",
        "me" => "application/x-troff-me",
        "ms" => "application/x-troff-ms",
        "ustar" => "application/x-ustar",
        "src" => "application/x-wais-source",
        "xhtml" => "application/xhtml+xml",
        "xht" => "application/xhtml+xml",
        "zip" => "application/zip",
        "au" => "audio/basic",
        "snd" => "audio/basic",
        "mid" => "audio/midi",
        "midi" => "audio/midi",
        "kar" => "audio/midi",
        "mpga" => "audio/mpeg",
        "mp2" => "audio/mpeg",
        "mp3" => "audio/mpeg",
        "aif" => "audio/x-aiff",
        "aiff" => "audio/x-aiff",
        "aifc" => "audio/x-aiff",
        "m3u" => "audio/x-mpegurl",
        "ram" => "audio/x-pn-realaudio",
        "rm" => "audio/x-pn-realaudio",
        "rpm" => "audio/x-pn-realaudio-plugin",
        "ra" => "audio/x-realaudio",
        "wav" => "audio/x-wav",
        "pdb" => "chemical/x-pdb",
        "xyz" => "chemical/x-xyz",
        "bmp" => "image/bmp",
        "gif" => "image/gif",
        "ief" => "image/ief",
        "jpeg" => "image/jpeg",
        "jpg" => "image/jpeg",
        "jpe" => "image/jpeg",
        "png" => "image/png",
        "tiff" => "image/tiff",
        "tif" => "image/tif",
        "djvu" => "image/vnd.djvu",
        "djv" => "image/vnd.djvu",
        "wbmp" => "image/vnd.wap.wbmp",
        "ras" => "image/x-cmu-raster",
        "pnm" => "image/x-portable-anymap",
        "pbm" => "image/x-portable-bitmap",
        "pgm" => "image/x-portable-graymap",
        "ppm" => "image/x-portable-pixmap",
        "rgb" => "image/x-rgb",
        "xbm" => "image/x-xbitmap",
        "xpm" => "image/x-xpixmap",
        "xwd" => "image/x-windowdump",
        "igs" => "model/iges",
        "iges" => "model/iges",
        "msh" => "model/mesh",
        "mesh" => "model/mesh",
        "silo" => "model/mesh",
        "wrl" => "model/vrml",
        "vrml" => "model/vrml",
        "css" => "text/css",
        "html" => "text/html",
        "htm" => "text/html",
        "asc" => "text/plain",
        "txt" => "text/plain",
        "rtx" => "text/richtext",
        "rtf" => "text/rtf",
        "sgml" => "text/sgml",
        "sgm" => "text/sgml",
        "tsv" => "text/tab-seperated-values",
        "wml" => "text/vnd.wap.wml",
        "wmls" => "text/vnd.wap.wmlscript",
        "etx" => "text/x-setext",
        "xml" => "text/xml",
        "xsl" => "text/xml",
        "mpeg" => "video/mpeg",
        "mpg" => "video/mpeg",
        "mpe" => "video/mpeg",
        "qt" => "video/quicktime",
        "mov" => "video/quicktime",
        "mxu" => "video/vnd.mpegurl",
        "avi" => "video/x-msvideo",
        "movie" => "video/x-sgi-movie",
        "ice" => "x-conference-xcooltalk"
      );
    }
  }

  # PHP does not have getmxr function on windows so I built one
	function win_getmxrr($hostname, &$mxhosts, &$mxweight=false) {
		if(strtoupper(substr(PHP_OS, 0, 3)) != "WIN") return;
		if(!is_array($mxhosts)) $mxhosts = array();
		if(empty($hostname)) return;
		$exec="nslookup -type=MX " . escapeshellarg($hostname);
		@exec($exec, $output);
		if(empty($output)) return;
		$i=-1;
		foreach($output as $line) {
			$i++;
			if(preg_match("/^$hostname\tMX preference =([0-9]+), mail exchanger =(.+)$/i", $line, $parts)) {
				$mxweight[$i] = trim($parts[1]);
				$mxhosts[$i] = trim($parts[2]);
			}
			if(preg_match("/responsible mail addr =(.+)$/i", $line, $parts)) {
				$mxweight[$i] = $i;
				$mxhosts[$i] = trim($parts[1]);
			}
		}
		return($i!=-1);
	}

	if(!function_exists("getmxrr")) {
		function getmxrr($hostname, &$mxhosts, &$mxweight=false) {
			return win_getmxrr($hostname, $mxhosts, $mxweight);
		}
	}

  # Since WordPress is smart and checkes if wp_mail function exists,
  # all we have to do is declare it here and wp_mail will not be loaded any more.
  # Of course we run a check before to make sure there's no conflict.
  if(!function_exists("wp_mail")) {
		function wp_mail($to, $subject, $message, $headers="", $attachments=""){
			$xmail = new XmailCore();

			$xmail->setFrom(get_option("admin_email"));
			$xmail->setHost($_SERVER["HTTP_HOST"]);
			$xmail->setPort(25);
			$xmail->setTime(30);

			# add headers if none
			if($headers == "")
			 $headers = "From: ".get_option("blogname")." <".get_option("admin_email").">" . $xmail->line;

      # implode array headers
      if(is_array($headers))
       $headers = implode($xmail->line, array_map('trim', $headers));

      # standardize lines
      $headers = preg_replace('/\r\n|\n|\r/', $xmail->line, $headers);

      # remove content type
      $headers = preg_replace('/(content-type: .*)((\r\n)(\t|\s)(.*))?/im', '', $headers);

      # remove empty lines
      $headers = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", $xmail->line, $headers);

      # ensure end of line
      $headers = trim($headers) . $xmail->line;

			# send the email
			$xmail->mail($to, $subject, $message, $headers, $attachments);

			// if you want to see transaction log uncomment this
			#echo "<pre>"; print_r($xmail->log); echo "</pre>";
		}
  }

  # Now the admin page
  add_action("admin_menu", "xmail_right_way_menu");

  function xmail_right_way_menu() {
    add_options_page("Xmail About", "Xmail", "manage_options", "the_right_way", "xmail_right_way_options");
  }

  function xmail_right_way_options() {
    if(!current_user_can("manage_options"))  {
      wp_die( __("You do not have sufficient permissions to access this page.") );
    }

?>
<div class="wrap">
  <div id="icon-options-general" class="icon32"></div>
  <h2>Xmail About</h2>
  <div>
    <br/>
    <table width="100%" border="0">
      <tr>
        <td width="10%" valign="top" style="background-color:#EEF5FB; padding:10px;"><strong>About</strong></td>
        <td width="90%" style="background-color:#EEEEEE; padding:10px;">
          Send email the right way so it does not get flagged as SPAM.
          <br/>Most servers use a diffrent IP address to send email from then the IP of your domain and thus your emails get into SPAM folders or not att all in some cases of Yahoo! and MSN.
          <br/><b>This will send emails from your domain IP address. It might take 1-2 seconds more to send it but it is worth it.</b>
        </td>
      </tr>
      <tr>
        <td width="10%" valign="top" style="background-color:#EEF5FB; padding:10px;"><strong>License</strong></td>
        <td width="90%" style="background-color:#EEEEEE; padding:10px;">
          <b>Copyright(C) <?php echo date(Y); ?> transilvlad</b>
          <br/>License: <b>GPL v2</b>
        </td>
      </tr>
    </table>
  </div>
</div>
<?php

  }

?>