<?php

/**
 * mime_mail class
 */
class mime_mail {
	/**
	 * properties
	 */
	protected $list_to = array();
	protected $list_cc = array();
	protected $list_bcc = array();
	protected $list_reply_to = array();
	protected $list_from = array();
	protected $list_images = array();
	protected $list_attachments = array();
	protected $list_headers = array();

	protected $text_subject = '';
	protected $text = '';
	protected $text_charset = 'utf-8';
	protected $text_encoding = '8bit';

	protected $html = '';
	protected $html_charset = 'utf-8';
	protected $html_encoding = '8bit';

	protected $mime_version = '1.0';
	protected $encoding = '8bit';
	protected $priority = 3;

	protected $headers = '';
	protected $message = '';
	protected $mail_regex = '';
	protected $crlf = "\n";
	
	public $error = '';
	/**
	 *
	 */
	public function mime_mail() {
		$atom = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';
		$domain = '([a-z0-9]([-a-z0-9]*[a-z0-9]+)?)';
		$this->mail_regex = '/^'.$atom.'+(\.'.$atom.'+)*@('.$domain.'{1,63}\.)+'.$domain.'{2,63}$/i';
	}
	/**
	 * set priority
	 * 1 = High, 3 = Normal, 5 = low
	 * @param int $priority
	 * @return bool
	 */
	public function set_priority($priority) {
		if(!in_array($priority, array(1, 3, 5))) {
			return false;
		}
		$this->priority = $priority;
		return true;
	}
	/**
	 *
	 */
	public function clear_lists() {
		$this->list_to = array();
		$this->list_cc = array();
		$this->list_bcc = array();
		$this->list_reply_to = array();
		$this->list_from = array();

		$this->list_images = array();
		$this->list_attachments = array();

		$this->error = '';
	}
	/**
	 *
	 */
	public function clear_texts() {
		$this->text_subject = '';
		$this->text = '';
		$this->html = '';

		$this->error = '';
	}
	/**
	 *
	 */
	public function clear() {
		$this->clear_lists();
		$this->clear_texts();
	}
	/**
	 *
	 * @param Array $items
	 * @return Array
	 */
	protected function get_list($items) {
		$list = array();
		foreach($items as $email => $name) {
			if(empty($name)) {
				$list[] = '<'.$email.'>';
			}
			else {
				$list[] = '"'.$name.'" <'.$email.'>';
			}
		}
		if(count($list) > 0) {
			return $list;
		}
		else {
			return false;
		}
	}
	/**
	 *
	 * @param string $filename
	 * @return string
	 */
	protected function get_mime_type($filename) {
		$mimes = array(
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'css' => 'text/css',
			'png' => 'image/png',
			'gif' => 'image/gif',
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'bmp' => 'image/bmp',
			'tif' => 'image/tiff',
			'bz2' => 'application/x-bzip',
			'gz' => 'application/x-gzip',
			'tar' => 'application/x-tar',
			'zip' => 'application/zip',
			'aif' => 'audio/aiff',
			'aiff' => 'audio/aiff',
			'mid' => 'audio/mid',
			'midi' => 'audio/mid',
			'mp3' => 'audio/mpeg',
			'ogg' => 'audio/ogg',
			'wav' => 'audio/wav',
			'wma' => 'audio/x-ms-wma',
			'asf' => 'video/x-ms-asf',
			'asx' => 'video/x-ms-asf',
			'avi' => 'video/avi',
			'mpg' => 'video/mpeg',
			'mpeg' => 'video/mpeg',
			'wmv' => 'video/x-ms-wmv',
			'wmx' => 'video/x-ms-wmx',
			'xml' => 'text/xml',
			'xsl' => 'text/xsl',
			'doc' => 'application/msword',
			'rtf' => 'application/msword',
			'xls' => 'application/excel',
			'pps' => 'application/vnd.ms-powerpoint',
			'ppt' => 'application/vnd.ms-powerpoint',
			'pdf' => 'application/pdf',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'psd' => 'image/psd',
			'swf' => 'application/x-shockwave-flash',
			'ra' => 'audio/vnd.rn-realaudio',
			'ram' => 'audio/x-pn-realaudio',
			'rm' => 'application/vnd.rn-realmedia',
			'rv' => 'video/vnd.rn-realvideo',
			'exe' => 'application/x-msdownload',
			'pls' => 'audio/scpls',
			'm3u' => 'audio/x-mpegurl',
		);
		if(strpos($filename, '.') !== false) {
			$ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
			if(isset($mimes[$ext])) {
				return $mimes[$ext];
			}
		}
		return 'application/octet-stream';
	}
	/**
	 *
	 * @param string $email
	 * @param string $name
	 * @return array
	 */
	protected function get_email($email, $name = '') {
		if(empty($name) && preg_match('/^(.*?)<(.*)>$/i', $email, $m)) {
			$email = $m[2];
			$name = $m[1];
		}
		$name = trim(trim($name), '"');
		if($this->injection_attempted($name) || $this->injection_attempted($email)) {
			$this->error .= "Injection attempted in email.\n";
			return false;
		}
		if(preg_match($this->mail_regex, $email)) {
			return array($email, $name);
		}
		return false;
	}
	/**
	 *
	 * @param string $text
	 * @return bool
	 */
	protected function injection_attempted($text) {
		$text = strtolower($text);
		$checks = array(
			"\n", "\r", "%0a", "%0d",
			"content-type:",
			"cc:",
			"to:",
			"from:",
			"mime-version",
			"content-transfer-encoding",
		);
		foreach($checks as $check) {
			if(strpos($text, $check) !== false) {
				return true;
			}
		}
		return false;
	}
	/**
	 *
	 * @param string $header
	 * @param string $value
	 */
	public function header($header, $value) {
		$this->list_headers[$header] = $value;
	}
	/**
	 *
	 * @param string $email
	 * @param string $name
	 * @return bool
	 */
	public function to($email, $name = '') {
		$result = $this->get_email($email, $name);
		if($result !== false) {
			$this->list_to[$result[0]] = $result[1];
			return true;
		}
		return false;
	}
	/**
	 *
	 * @param string $email
	 * @param string $name
	 * @return bool
	 */
	public function cc($email, $name = '') {
		$result = $this->get_email($email, $name);
		if($result !== false) {
			$this->list_cc[$result[0]] = $result[1];
			return true;
		}
		return false;
	}
	/**
	 *
	 * @param string $email
	 * @param string $name
	 * @return bool
	 */
	public function bcc($email, $name = '') {
		$result = $this->get_email($email, $name);
		if($result !== false) {
			$this->list_bcc[$result[0]] = $result[1];
			return true;
		}
		return false;
	}
	/**
	 *
	 * @param string $email
	 * @param string $name
	 * @return bool
	 */
	public function reply_to($email, $name = '') {
		$result = $this->get_email($email, $name);
		if($result !== false) {
			$this->list_reply_to = array($result[0] => $result[1]);
			return true;
		}
		return false;
	}
	/**
	 *
	 * @param string $email
	 * @param string $name
	 * @return bool
	 */
	public function from($email, $name = '') {
		$result = $this->get_email($email, $name);
		if($result !== false) {
			$this->list_from = array($result[0] => $result[1]);
			return true;
		}
		return false;
	}
	/**
	 *
	 * @param string $text
	 * @param string $charset
	 * @param string $encoding
	 */
	public function body_text($text, $charset = 'utf-8', $encoding = '8bit') {
		$text = str_replace("\r", "", $text);
		$text = str_replace("\n", $this->crlf, $text);
		$this->text = wordwrap($text, 75, $this->crlf, false);
		$this->text_charset = $charset;
		$this->text_encoding = $encoding;
	}
	/**
	 *
	 * @param string $html
	 * @param string $charset
	 * @param string $encoding
	 */
	public function body_html($html, $charset = 'utf-8', $encoding = '8bit') {
		$this->html_charset = $charset;
		$this->html_encoding = $encoding;
		$this->html = str_replace("\r", "", $html);
		$this->html = str_replace("\n", $this->crlf, $this->html);
		$this->html = str_replace("\t", "", $this->html);
		$this->detect_embedded_image();
		if(empty($this->text)) {
            if(!class_exists('html2text')) {
                $text = preg_replace('/<br ?\/?>/si', $this->crlf, $html);
                $text = preg_replace('/<\/p>/si', $this->crlf, $text);
                $text = preg_replace('/<\/div>/si', $this->crlf, $text);
                $text = strip_tags($text);
            }
            else {
                $converter = new html2text($html);
                $text = $converter->get_text();
            }
			$this->body_text($text, $charset, $encoding);
		}
		$this->html = wordwrap($this->html, 75, $this->crlf, false);
	}
	/**
	 *
	 * @param string $email
	 * @param string $name
	 * @return bool
	 */
	protected function detect_embedded_image() {
		$items = array();
		if(preg_match_all("/ (?:src|background)=\"(.*?)\"/si", $this->html, $images, PREG_SET_ORDER)) {
			foreach($images as $image) {
				$image_name = trim(trim($image[1]), '"\'');
				if(!preg_match('/^[a-z]+?:/si', $image_name) && file_exists($image_name)) {
					if(isset($items[$image_name])) {
						$items[$image_name][] = $image[0];
					}
					else {
						$items[$image_name] = array($image[0]);
					}
				}
			}
		}
		if(count($items) > 0) {
			$this->list_images = array();
		}
		foreach($items as $image => $links) {
			$cid = 'img'.substr(md5(uniqid(mt_rand())), 0, 16);
			$this->add_image($cid, $image);
			foreach($links as $link) {
				$newlink = str_replace($image, 'cid:'.$cid, $link);
				$this->html = str_replace($link, $newlink, $this->html);
			}
		}
	}
	/**
	 *
	 * @param string $email
	 * @param string $name
	 * @return bool
	 */
	public function add_attachment($filename, $inline = false) {
		$this->list_attachments[$filename] = $inline;
	}
	/**
	 *
	 * @param string $id
	 * @param string $filename
	 */
	public function add_image($id, $filename) {
		$this->list_images[$id] = $filename;
	}
	/**
	 *
	 * @param string $subject
	 */
	public function subject($subject) {
		$this->text_subject = $subject;
	}
	/**
	 *
	 * @return bool
	 */
	protected function build() {
		// init

		$this->headers = '';
		$this->message = '';

		$boundary1 = '----------b1'.substr(md5(uniqid(mt_rand())), 0, 24);
		$boundary2 = '----------b2'.substr(md5(uniqid(mt_rand())), 0, 24);
		$boundary3 = '----------b3'.substr(md5(uniqid(mt_rand())), 0, 24);

		$to = $this->get_list($this->list_to);
		$cc = $this->get_list($this->list_cc);
		$bcc = $this->get_list($this->list_bcc);
		$from = $this->get_list($this->list_from);
		$reply_to = $this->get_list($this->list_reply_to);

		// check error
		if(!empty($this->error)) {
			return false;
		}

		// check to
		if(!is_array($to) || count($to) != 1) {
			$this->error .= "To is not defined.\n";
			return false;
		}

		// check from
		if(!is_array($from) || count($from) != 1) {
			$this->error .= "From is not defined.\n";
			return false;
		}

		// check subject
		if($this->injection_attempted($this->text_subject)) {
			$this->error .= "Injection attempted in subject.\n";
			return false;
		}

		// check body
		if(empty($this->text) && empty($this->html)) {
			$this->error .= "Body is not defined.\n";
			return false;
		}

		// headers
		$this->headers .= 'From: '.$from[0].$this->crlf;
		$this->headers .= 'Return-Path: '.$from[0].$this->crlf;

		if(is_array($reply_to) && count($reply_to) > 0) {
			$this->headers .= 'Reply-To: '.$reply_to[0].$this->crlf;
		}

		if(is_array($cc)) {
			foreach($cc as $tmp) {
				$this->headers .= 'Cc: '.$tmp.$this->crlf;
			}
		}

		if(is_array($bcc)) {
			foreach($bcc as $tmp) {
				$this->headers .= 'Bcc: '.$tmp.$this->crlf;
			}
		}

		$headers = $this->list_headers;
		$headers['MIME-Version'] = $this->mime_version;
		$headers['X-Priority'] = $this->priority;
		$headers['X-Mailer: PHP/'.phpversion()] = $this->mime_version;

		foreach($headers as $header => $value) {
			$this->headers .= $header.': '.$value.$this->crlf;
		}

		$this->headers .= 'Content-Type: multipart/mixed;'.$this->crlf.' boundary="'.$boundary1.'"'.$this->crlf;
		$this->headers .= 'Content-Transfer-Encoding: '.$this->encoding.$this->crlf;

		// message
		$this->message .= '--'.$boundary1.$this->crlf;
		$this->message .= 'Content-Type: multipart/alternative;'.$this->crlf.' boundary="'.$boundary2.'"'.$this->crlf.$this->crlf.$this->crlf;
		if(!empty($this->text)) {
			$this->message .= '--'.$boundary2.$this->crlf;
			$this->message .= 'Content-Type: text/plain; charset="'.$this->text_charset.'"'.$this->crlf;
			$this->message .= 'Content-Transfer-Encoding: '.$this->text_encoding.$this->crlf;
			$this->message .= $this->crlf;
			$this->message .= $this->text.$this->crlf.$this->crlf;
		}
		if(!empty($this->html)) {
			$this->message .= '--'.$boundary2.$this->crlf;
			$this->message .= 'Content-Type: multipart/related;'.$this->crlf.' boundary="'.$boundary3.'"'.$this->crlf.$this->crlf.$this->crlf;
			$this->message .= '--'.$boundary3.$this->crlf;
			$this->message .= 'Content-Type: text/html; charset="'.$this->html_charset.'"'.$this->crlf;
			$this->message .= 'Content-Transfer-Encoding: '.$this->html_encoding.$this->crlf;
			$this->message .= $this->crlf;
			$this->message .= $this->html.$this->crlf.$this->crlf;

			foreach($this->list_images as $id => $filename) {
				if(file_exists($filename)) {
					$fh = fopen($filename, 'rb');
					$base64 = fread($fh, filesize($filename));
					fclose($fh);
					$base64 = chunk_split(base64_encode($base64), 76, $this->crlf);

					$mime_type = $this->get_mime_type($filename);

					$this->message .= '--'.$boundary3.$this->crlf;
					$this->message .= 'Content-Type: '.$mime_type.';'.$this->crlf.' name="'.basename($filename).'"'.$this->crlf;
					$this->message .= 'Content-Transfer-Encoding: base64'.$this->crlf;
					$this->message .= 'Content-ID: <'.$id.'>'.$this->crlf;
					$this->message .= 'Content-Disposition: inline;'.$this->crlf.' filename="'.basename($filename).'"'.$this->crlf;
					$this->message .= $this->crlf;
					$this->message .= $base64.$this->crlf;
				}
			}
			$this->message .= '--'.$boundary3.'--'.$this->crlf.$this->crlf;
		}
		$this->message .= '--'.$boundary2.'--'.$this->crlf.$this->crlf;
		foreach($this->list_attachments as $filename => $inline) {
			if(file_exists($filename)) {
				$fh = fopen($filename, 'rb');
				$base64 = fread($fh, filesize($filename));
				fclose($fh);
				$base64 = chunk_split(base64_encode($base64), 76, $this->crlf);

				$mime_type = $this->get_mime_type($filename);

				$this->message .= '--'.$boundary1.$this->crlf;
				$this->message .= 'Content-Type: '.$mime_type.';'.$this->crlf.' name="'.basename($filename).'"'.$this->crlf;
				$this->message .= 'Content-Transfer-Encoding: base64'.$this->crlf;
				if($inline) {
					$this->message .= 'Content-Disposition: inline;'.$this->crlf.' filename="'.basename($filename).'"'.$this->crlf;
				}
				else {
					$this->message .= 'Content-Disposition: attachment;'.$this->crlf.' filename="'.basename($filename).'"'.$this->crlf;
				}
				$this->message .= $this->crlf;
				$this->message .= $base64.$this->crlf;
			}
		}
		$this->message .= '--'.$boundary1.'--'.$this->crlf.$this->crlf;
		return true;
	}
	/**
	 *
	 * @return <type>
	 */
	public function send() {
		if($this->build()) {
			$to = implode(',', $this->get_list($this->list_to));
			if(@mail($to, $this->text_subject, $this->message, $this->headers)) {
				return true;
			}
		}
		return false;
	}

}

?>