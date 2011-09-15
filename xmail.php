<?php
/*
Plugin Name: Xmail - The Right Way
Plugin URI: http://www.endd.eu/xmail-email-the-right-way/
Description: Send email the right way so it does not get flagged as SPAM. Most servers use a diffrent IP address to send email from then the IP of your domain and thus your emails get into SPAM folders or not att all in some cases of Yahoo! and MSN. This will send emails from your domain IP address. It might take 1-2 seconds more to send it but it is worth it.
Version: 1.00 Baby
Author: Marian Vlad-Marian
Author URI: http://www.lantian.eu/
License: GPL v.2

             GNU
   GENERAL PUBLIC LICENSE
    Version 2, June 1991

 Copyright (C) 2011 endd.ro
  
*/
  
  # DEBUG MODE
#  error_reporting(E_ALL & ~E_NOTICE);
  
  # WP version check
  global $wp_version;
  $exit_msg='Plugin requires WordPress 3.0 or newer.<a href="http://wordpress.org/" target="_blank">Please update!</a>';
  if(version_compare ($wp_version,"3.0","<")){exit($exit_msg);}
  
  class XmailBaby{
    
    # LOG VAR
    public $log = Array();
    
    # NEW LINE
    private $line = "\r\n";
    
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
      
      $boundary1 = '-----='.md5(uniqid(rand()));
      $boundary2 = '-----='.md5(uniqid(rand()));
                 
      $message .= "\nThis is a multi-part message in MIME format.\n\n";
      $message .= "--".$boundary1."\n";
      $message .= "Content-Type: multipart/alternative;\n      boundary=\"$boundary2\"\n\n\n";
            
      # MESSAGE TEXT
      $message .= "--".$boundary2."\n";
      $message .= "Content-Type: text/plain;\n      charset=\"us-ascii\"\n";
      $message .= "Content-Transfer-Encoding: 7bit\n\n";
      $message .= strip_tags($msg) . "\n";
      $message .= "\n\n";
                 
      # MESSAGE HTML
      $message .= "--".$boundary2."\n";
      $message .= "Content-Type: text/html;\n      charset=\"us-ascii\"\n";
      $message .= "Content-Transfer-Encoding: quoted-printable\n\n";
      $message .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n";
      $message .= "<html>\n";
      $message .= "<body>\n";
      $message .= $msg . "<br/>\n";
      $message .= "</body>\n";
      $message .= "</html>\n\n";
      $message .= "--".$boundary2."--\n\n";
      
      if(is_array($attachments)) {
        foreach($attachments AS $file_url) {
          if(is_file($file_url)) {
            $file_name = pathinfo($file_url, PATHINFO_BASENAME);
            $file_type = $this->find_mime(pathinfo($file_url, PATHINFO_EXTENSION));
            
            # ATTACHMENT
            $message .= "--".$boundary1."\n";
            $message .= "Content-Type: ".$file_type.";\n      name=\"$file_name\"\n";
            $message .= "Content-Transfer-Encoding: base64\n";
            $message .= "Content-Disposition: attachment;\n      filename=\"$file_name\"\n\n";
                      
            $fp = fopen($file_url, 'r');
            do {
              $data = fread($fp, 8192);
              if (strlen($data) == 0) break;
              $content .= $data;
            }
            while (true);
            $content_encode = chunk_split(base64_encode($content));
            $message .= $content_encode."\n\n";
            $content = '';
            unset($content); 
  
          }
        }
      }
      $message .= "--".$boundary1."--\n\n";
      
      $headers .= "MIME-Version: 1.0\n";
      $headers .= "Content-Type: multipart/mixed;\n      boundary=\"$boundary1\"\n";
      
      return $this->sokmail($to, $subject, $message, $headers);
    }
    
    # send mail directly to destination MX server
    function sokmail($to, $subject, $message, $headers) {
      list($user, $domain) = split("@",$to);
      getmxrr($domain, $mxhosts);
      $server = $mxhosts['0'];
      
      # open socket
      $socket = @fsockopen($server, $this->port, $errno, $errstr, $this->time);
      if(empty($socket)) { return false; }
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
      $response = '';
      $this->log[$cmd] = "";
      while (substr($response, 3, 1) != ' ') {
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
      if (isset($mimetypes[$ext])) {
        return $mimetypes[$ext];
      # if the extension wasn't found return octet-stream         
      } else {
        return 'application/octet-stream';
      }
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
		if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') return;
		if (!is_array ($mxhosts) ) $mxhosts = array();
		if (empty($hostname)) return;
		$exec='nslookup -type=MX '.escapeshellarg($hostname);
		@exec($exec, $output);
		if (empty($output)) return;
		$i=-1;
		foreach ($output as $line) {
			$i++;
			if (preg_match("/^$hostname\tMX preference = ([0-9]+), mail exchanger = (.+)$/i", $line, $parts)) {
				$mxweight[$i] = trim($parts[1]);
				$mxhosts[$i] = trim($parts[2]);
			}
			if (preg_match('/responsible mail addr = (.+)$/i', $line, $parts)) {
				$mxweight[$i] = $i;
				$mxhosts[$i] = trim($parts[1]);
			}
		}
		return ($i!=-1);
	}
	 
	if (!function_exists('getmxrr')) {
		function getmxrr($hostname, &$mxhosts, &$mxweight=false) {
			return win_getmxrr($hostname, $mxhosts, $mxweight);
		}
	}
  
  # Since WordPress is smart and checkes if wp_mail function exists, 
  # all we have to do is declare it here and wp_mail will not be loaded any more.
  # Of course we run a check before to make sure there's no conflict.
  if (!function_exists('wp_mail')) {
		function wp_mail($to, $subject, $message, $headers="", $attachments=""){
			$xmail = new XmailBaby();
			
			$xmail->setFrom(get_option('admin_email'));
			$xmail->setHost($_SERVER['HTTP_HOST']);
			$xmail->setPort(25);
			$xmail->setTime(30);
			
			# add headers if none
			if($headers == "") $headers = "From: ".get_option('blogname')." <".get_option('admin_email').">\r\n";
			
			# send the email
			$xmail->mail($to, $subject, $message, $headers, $attachments);
			
			// if you want to see transaction log uncomment this
#			echo "<pre>"; print_r($xmail->log); echo "</pre>";
		}
  }
  
  # Now the admin page
  add_action('admin_menu', 'xmail_right_way_menu');

  function xmail_right_way_menu() {
    add_options_page('Xmail About', 'Xmail', 'manage_options', 'the_right_way', 'xmail_right_way_options');
  }

  function xmail_right_way_options() {
    if (!current_user_can('manage_options'))  {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    
    echo '<div class="wrap">';
    echo '  <div id="icon-options-general" class="icon32"></div>';
    echo "  <h2>Xmail About</h2>";
    echo '  <div>';
    
?>
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
			<b>Copyright (C) <?php echo date(Y); ?> endd.ro</b>
			<br/><b>Attribution-NonCommercial-ShareAlike</b>
			<br/>(CC BY-NC-SA)
			<br/>
			<br/>This license lets others remix, tweak, and build upon this work non-commercially, as long as they credit the author and license their new creations under the identical terms.
		</td>
	</tr>

	<tr>
		<td width="10%" valign="top" style="background-color:#EEF5FB; padding:10px;"><strong style="color:red;">Upgrade</strong></td>
		<td width="90%" style="background-color:#EEEEEE; padding:10px; color:red;">
			Upgrade to Professional version and you get a fully configurable email sender tool that will help you get all your email sending problems fixed.
			<br/><b>The professional version is FREE!</b> All we ask in return is a credit link.
			<br/>
			<br/>With the professional version you get:
			<br/><i>&nbsp;- Customize the sender email address and name.</i>
			<br/><i>&nbsp;- Create a HTML template to be used in all your emails. This will give you the flexibility to design your emails.</i>
			<br/><i>&nbsp;- Options to select the way you send emails: mail [php method], SMTP [via an email account], MX [Xmail way]</i>
			<br/><i>&nbsp;- Run in test mode to see what way is better for you.</i>
			<br/>
			<br/><a href="http://www.endd.eu/xmail-email-the-right-way/" target="_blank">CLICK HERE TO UPGRADE</a>
    </td>
	</tr>
	
</table>
<?php
    echo '  </div>';
    echo '</div>';
  }
  
?>