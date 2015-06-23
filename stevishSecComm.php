<?php
/*ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

$ssc = new StevishSecComm('keykeykeykeykeykey', 'iv');



echo $ssc->decrypt( "c58fdb7254365b0c93c7dcf11b421e8c70c4de13c494323359195b998d3a459e" );
echo "<br/>";
echo $ssc->decrypt("569e0bdb9583dc0c34f9469f2dc01bdd030233e3825a74b485bf0a24f9859dfa");
echo $ssc->decrypt("569e0bdb9583dc0c34f9469f2dc01bdd5735fb094a4c5623b4857fe9a34209a5");
*/
class StevishSecComm {
	private $iv;
	private $key;
 
    public function __construct($key, $iv) {
		$this->key = $this->pad_key( $key );
		$this->iv = $this->pad_key( $iv );
    }
 
	public function encrypt_time( $str ) {
		$now = time();
		return $this->encrypt( $now . "#" . $str );
	}
 
    public function encrypt( $str ) { 
      $str = $this->pkcs5_pad($str);   
      $iv = $this->iv; 
      $td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv); 
      mcrypt_generic_init($td, $this->key, $iv);
      $encrypted = mcrypt_generic($td, $str); 
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td); 
      return bin2hex($encrypted);
    }
	
	public function decrypt_time( $code, $error_factor ) {
		$now = time();
		$text = explode( "#", $this->decrypt($code), 2);
		if ( abs($now - $text[0]) > $error_factor ) {
			return false;
		} else {
			return $text[1];
		}
	}
 
    public function decrypt( $code ) { 
      $code = $this->hex2bin($code);
      $iv = $this->iv; 
      $td = mcrypt_module_open('rijndael-128', '', 'cbc', $iv); 
      mcrypt_generic_init($td, $this->key, $iv);
      $decrypted = mdecrypt_generic($td, $code); 
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td); 
      $ut =  utf8_encode(trim($decrypted));
      return $this->pkcs5_unpad($ut);
    }
 
    protected function hex2bin( $hexdata ) {
      $bindata = ''; 
      for ($i = 0; $i < strlen($hexdata); $i += 2) {
          $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
      } 
      return $bindata;
    } 
 
    protected function pkcs5_pad( $text ) {
      $blocksize = 16;
      $pad = $blocksize - (strlen($text) % $blocksize);
      return $text . str_repeat(chr($pad), $pad);
    }
 
    protected function pkcs5_unpad( $text ) {
      $pad = ord($text{strlen($text)-1});
      if ($pad > strlen($text)) {
          return $text; 
      }
      if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
          return $text;
      }
      return substr($text, 0, -1 * $pad);
    }
	
	private function pad_key( $in ) {
		if ( strlen( $in ) < 16 ) {
			for( $i = 16 - strlen( $in ); $i > 0; $i-- ) {
				$in .= "0";
			}
		} elseif ( strlen( $in ) > 16 ) {
			$in = substr( $in, 0, 16 );
		}
		return $in;
	}
}
