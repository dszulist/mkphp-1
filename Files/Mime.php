<?php
/**
 * MK_Files_Mime
 *
 * Klasa do obsługi/ rozpoznawania MIME wzieta z Docflow
 * nazwyała sie openfile
 *
 * @category	MK_Files
 * @package		MK_Files_Mime
 */
class MK_Files_Mime {

	private $filename;
	protected $imageType;

	var $availbeImagesExt =  array('jpg','jpeg','gif','png');

	var $mime_map = array(
		'ai'	=>	'application/postscript',
		'aif'	=>	'audio/x-aiff',
		'aifc'	=>	'audio/x-aiff',
		'aiff'	=>	'audio/x-aiff',
		'asc'	=>	'text/plain',
		'au'	=>	'audio/basic',
		'avi'	=>	'video/x-msvideo',
		'bcpio'	=>	'application/x-bcpio',
		'bin'	=>	'application/octet-stream',
		'bmp'	=>	'image/bmp',
		'c'		=>	'text/plain',
		'cc'	=>	'text/plain',
		'ccad'	=>	'application/clariscad',
		'cdf'	=>	'application/x-netcdf',
		'class'	=>	'application/octet-stream',
		'cpio'	=>	'application/x-cpio',
		'cpt'	=>	'application/mac-compactpro',
		'csh'	=>	'application/x-csh',
		'css'	=>	'text/css',
		'dcr'	=>	'application/x-director',
		'dir'	=>	'application/x-director',
		'dms'	=>	'application/octet-stream',
		'doc'	=>	'application/msword',
		'drw'	=>	'application/drafting',
		'dvi'	=>	'application/x-dvi',
		'dwg'	=>	'application/acad',
		'dxf'	=>	'application/dxf',
		'dxr'	=>	'application/x-director',
		'eps'	=>	'application/postscript',
		'etx'	=>	'text/x-setext',
		'exe'	=>	'application/octet-stream',
		'ez'	=>	'application/andrew-inset',
		'f'		=>	'text/plain',
		'f90'	=>	'text/plain',
		'fli'	=>	'video/x-fli',
		'gif'	=>	'image/gif',
		'gtar'	=>	'application/x-gtar',
		'gz'	=>	'application/x-gzip',
		'h'		=>	'text/plain',
		'hdf'	=>	'application/x-hdf',
		'hh'	=>	'text/plain',
		'hqx'	=>	'application/mac-binhex40',
		'htm'	=>	'text/html',
		'html'	=>	'text/html',
		'ice'	=>	'x-conference/x-cooltalk',
		'ief'	=>	'image/ief',
		'iges'	=>	'model/iges',
		'igs'	=>	'model/iges',
		'ips'	=>	'application/x-ipscript',
		'ipx'	=>	'application/x-ipix',
		'jpe'	=>	'image/jpeg',
		'jpeg'	=>	'image/jpeg',
		'jpg'	=>	'image/jpeg',
		'js'	=>	'application/x-javascript',
		'kar'	=>	'audio/midi',
		'latex'	=>	'application/x-latex',
		'lha'	=>	'application/octet-stream',
		'lsp'	=>	'application/x-lisp',
		'lzh'	=>	'application/octet-stream',
		'm'		=>	'text/plain',
		'man'	=>	'application/x-troff-man',
		'me'	=>	'application/x-troff-me',
		'mesh'	=>	'model/mesh',
		'mid'	=>	'audio/midi',
		'midi'	=>	'audio/midi',
		'mif'	=>	'application/vnd.mif',
		'mime'	=>	'www/mime',
		'mov'	=>	'video/quicktime',
		'movie'	=>	'video/x-sgi-movie',
		'mp2'	=>	'audio/mpeg',
		'mp3'	=>	'audio/mpeg',
		'mpe'	=>	'video/mpeg',
		'mpeg'	=>	'video/mpeg',
		'mpg'	=>	'video/mpeg',
		'mpga'	=>	'audio/mpeg',
		'ms'	=>	'application/x-troff-ms',
		'msh'	=>	'model/mesh',
		'nc'	=>	'application/x-netcdf',
		'oda'	=>	'application/oda',
		'odp'	=>	'application/vnd.oasis.opendocument.presentation',
		'ods'	=>	'application/vnd.oasis.opendocument.spreadsheet',
		'odt'	=>	'application/vnd.oasis.opendocument.text',
		'pbm'	=>	'image/x-portable-bitmap',
		'pdb'	=>	'chemical/x-pdb',
		'pdf'	=>	'application/pdf',
		'pgm'	=>	'image/x-portable-graymap',
		'pgn'	=>	'application/x-chess-pgn',
		'php'	=>	'text/plain',
		'php3'	=>	'text/plain',
		'png'	=>	'image/png',
		'pnm'	=>	'image/x-portable-anymap',
		'pot'	=>	'application/mspowerpoint',
		'ppm'	=>	'image/x-portable-pixmap',
		'pps'	=>	'application/mspowerpoint',
		'ppt'	=>	'application/mspowerpoint',
		'ppz'	=>	'application/mspowerpoint',
		'pre'	=>	'application/x-freelance',
		'prt'	=>	'application/pro_eng',
		'ps'	=>	'application/postscript',
		'qt'	=>	'video/quicktime',
		'ra'	=>	'audio/x-realaudio',
		'ram'	=>	'audio/x-pn-realaudio',
		'ras'	=>	'image/cmu-raster',
		'rgb'	=>	'image/x-rgb',
		'rm'	=>	'audio/x-pn-realaudio',
		'roff'	=>	'application/x-troff',
		'rpm'	=>	'audio/x-pn-realaudio-plugin',
		'rtf'	=>	'text/rtf',
		'rtx'	=>	'text/richtext',
		'scm'	=>	'application/x-lotusscreencam',
		'set'	=>	'application/set',
		'sgm'	=>	'text/sgml',
		'sgml'	=>	'text/sgml',
		'sh'	=>	'application/x-sh',
		'shar'	=>	'application/x-shar',
		'silo'	=>	'model/mesh',
		'sit'	=>	'application/x-stuffit',
		'skd'	=>	'application/x-koan',
		'skm'	=>	'application/x-koan',
		'skp'	=>	'application/x-koan',
		'skt'	=>	'application/x-koan',
		'smi'	=>	'application/smil',
		'smil'	=>	'application/smil',
		'snd'	=>	'audio/basic',
		'sol'	=>	'application/solids',
		'spl'	=>	'application/x-futuresplash',
		'src'	=>	'application/x-wais-source',
		'step'	=>	'application/STEP',
		'stl'	=>	'application/SLA',
		'stp'	=>	'application/STEP',
		'sv4cpio'	=>	'application/x-sv4cpio',
		'sv4crc'	=>	'application/x-sv4crc',
		'swf'	=>	'application/x-shockwave-flash',
		'sxw'	=>	'application/vnd.sun.xml.writer',
		't'		=>	'application/x-troff',
		'tar'	=>	'application/x-tar',
		'tcl'	=>	'application/x-tcl',
		'tex'	=>	'application/x-tex',
		'texi'	=>	'application/x-texinfo',
		'texinfo'	=>	'application/x-texinfo',
		'tif'	=>	'image/tiff',
		'tiff'	=>	'image/tiff',
		'tr'	=>	'application/x-troff',
		'tsi'	=>	'audio/TSP-audio',
		'tsp'	=>	'application/dsptype',
		'tsv'	=>	'text/tab-separated-values',
		'txt'	=>	'text/plain',
		'unv'	=>	'application/i-deas',
		'ustar'	=>	'application/x-ustar',
		'vcd'	=>	'application/x-cdlink',
		'vda'	=>	'application/vda',
		'viv'	=>	'video/vnd.vivo',
		'vivo'	=>	'video/vnd.vivo',
		'vrml'	=>	'model/vrml',
		'wav'	=>	'audio/x-wav',
		'wrl'	=>	'model/vrml',
		'xbm'	=>	'image/x-xbitmap',
		'xlc'	=>	'application/vnd.ms-excel',
		'xll'	=>	'application/vnd.ms-excel',
		'xlm'	=>	'application/vnd.ms-excel',
		'xls'	=>	'application/vnd.ms-excel',
		'xlw'	=>	'application/vnd.ms-excel',
		'xml'	=>	'text/xml',
		'xpm'	=>	'image/x-xpixmap',
		'xwd'	=>	'image/x-xwindowdump',
		'xyz'	=>	'chemical/x-pdb',
		'zip'	=>	'application/zip'
	);

	var $dublin_core_map = array(
		'asc'	=>	'Text',
		'avi'	=>	'Moving Image',
		'bmp'	=>	'Image',
		'c'		=>	'Software',
		'class'	=>	'Software',
		'csh'	=>	'Software',
		'css'	=>	'Text',
		'doc'	=>	'Text',
		'dvi'	=>	'Image',
		'eps'	=>	'Image',
		'exe'	=>	'Software',
		'fli'	=>	'Moving Image',
		'gif'	=>	'Image',
		'gtar'	=>	'Collection',
		'gz'	=>	'Collection',
		'h'		=>	'Software',
		'htm'	=>	'Text',
		'html'	=>	'Text',
		'ief'	=>	'Image',
		'jpe'	=>	'Image',
		'jpeg'	=>	'Image',
		'jpg'	=>	'Image',
		'js'	=>	'Software',
		'kar'	=>	'Sound',
		'lsp'	=>	'Software',
		'mid'	=>	'Sound',
		'midi'	=>	'Sound',
		'mov'	=>	'Moving Image',
		'movie'	=>	'Moving Image',
		'mp2'	=>	'Sound',
		'mp3'	=>	'Sound',
		'mpe'	=>	'Moving Image',
		'mpeg'	=>	'Moving Image',
		'mpg'	=>	'Moving Image',
		'mpga'	=>	'Moving Image',
		'odp'	=>	'Collection',
		'ods'	=>	'Text',
		'odt'	=>	'Text',
		'pbm'	=>	'Image',
		'pdf'	=>	'Text',
		'php'	=>	'Software',
		'php3'	=>	'Software',
		'png'	=>	'Image',
		'pnm'	=>	'Image',
		'pps'	=>	'Collection',
		'ppt'	=>	'Collection',
		'ps'	=>	'Software',
		'qt'	=>	'Moving Image',
		'ra'	=>	'Sound',
		'ram'	=>	'Moving Image',
		'rgb'	=>	'Image',
		'rm'	=>	'Moving Image',
		'rtf'	=>	'Text',
		'rtx'	=>	'Text',
		'sgm'	=>	'Text',
		'sgml'	=>	'Text',
		'snd'	=>	'Sound',
		'swf'	=>	'Software',
		'tar'	=>	'Collection',
		'tif'	=>	'Image',
		'tiff'	=>	'Image',
		'txt'	=>	'Text',
		'wav'	=>	'Sound',
		'xbm'	=>	'Image',
		'xls'	=>	'Text',
		'xml'	=>	'Text',
		'zip'	=>	'Collection'
	);


    /**
     * pobranie rozszerzenia pliku
     *
     * @return string / boolean
     */
    function getExtention(){
     	$table = (explode(".", $this->filename));
    	if(!empty($table)){
    		return strtolower(end($table));
    	}
    	return false;
    }


    /**
     * pobranie typu mime
     *
     * @param string ext
     * @return string
     */
    function getMime($ext){
	    if(array_key_exists($ext,$this->mime_map)){
			return $this->mime_map[$ext];
	    }
		else{
			return false;
		}
    }


    function getExtByMime($mime){
    	return array_search($mime, $this->mime_map);
    }

    /**
     * Pobranie typu zgodnego z Dublin Meta Core
     * link: http://dublincore.org/documents/dcmi-type-vocabulary/
     *
     * @param string ext
     * @return string
     */
    function getDublinCoreResType($ext){
	    if(array_key_exists($ext,$this->dublin_core_map)){
			return $this->dublin_core_map[$ext];
	    }
		else{
			return false;
		}
    }


    /**
     * Stworzenie z filename jesli jest jpg obrazka
     *
     * @param string typ
     * @return bool
     */
    function createImageFile($type = 'jpg'){

      	switch($type){
    			case 'jpg' : case 'jpeg' :
				$im = imagecreatefromjpeg($this->filename);
				break;
			case 'gif' :
				$im = imagecreatefromgif($this->filename);
				break;
			case 'png' :
				$im = imagecreatefrompng($this->filename);
				break;
    		}
    		if(!isset($im)){
    			return false;
    		}
    		$max_x = 768;
			$max_y = 1024;
			$x = imagesx($im);
			$y = imagesy($im);
			if ($x > $max_x || $y > $max_y) {
				if (($max_x / $max_y) < ($x / $y)) {
					$new_x = $x / ($x / $max_x);
					$new_y = $y / ($x / $max_x);
					$save = imagecreatetruecolor($new_x, $new_y);
				} else {
					$new_x = $x / ($y / $max_y);
					$new_y = $y / ($y / $max_y);
					$save = imagecreatetruecolor($new_x, $new_y);
				}
//				$new_uid = uniqid("");
//				$date_temp = date("Y-m-d H:i:s");
//				$zalacznik_helper = new zalacznik_helper();
//				$path_with_date = $zalacznik_helper->parse_createdate_to_path($date_temp);
//				if(!is_dir(DirectoriesConfig::$directories['tempDirectory'].$path_with_date)){
//					$zalacznik_helper->tworz_katalogi_wg_createdate(DirectoriesConfig::$directories['tempDirectory'],$date_temp);
//				}
//				$file_path = DirectoriesConfig::$directories['tempDirectory'].$path_with_date.$new_uid.".jpg";
				imagecopyresized($save, $im, 0, 0, 0, 0, $new_x, $new_y, $x, $y);
//				imagejpeg($save, $file_path);
//				$this->filename = $file_path;
			}
        return null;
    }


    /**
     * Sprawdzenie czy plik jest obrazkiem
     *
     * @return boolean
     */
    function isImage(){
    	$ext = $this->getExtention();
    	if(in_array($ext, $this->availbeImagesExt)){
    		$this->imageType = $ext;
    		return true;
    	}

        return false;

    }


    /**
     * Pobranie zawartosci pliku
     *
     * @return string
     */
    function fileGetContents(){
    	return file_get_contents($this->filename);
    }

}
