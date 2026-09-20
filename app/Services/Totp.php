<?php
namespace App\Services;
class Totp {
 private const ALPHABET='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
 public function secret(int $length=20): string {$raw=random_bytes($length);$bits='';foreach(str_split($raw) as $c)$bits.=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);$out='';foreach(str_split($bits,5) as $chunk){if(strlen($chunk)<5)$chunk=str_pad($chunk,5,'0');$out.=self::ALPHABET[bindec($chunk)];}return $out;}
 public function verify(string $secret,string $code): bool {if(!preg_match('/^\d{6}$/',$code))return false;$counter=intdiv(time(),30);for($i=-1;$i<=1;$i++)if(hash_equals($this->code($secret,$counter+$i),$code))return true;return false;}
 public function uri(string $secret,string $email): string {return 'otpauth://totp/'.rawurlencode('Auliachem CMS:'.$email).'?secret='.$secret.'&issuer='.rawurlencode('Auliachem CMS');}
 private function code(string $secret,int $counter): string {$key=$this->decode($secret);$bin=pack('N2',0,$counter);$hash=hash_hmac('sha1',$bin,$key,true);$offset=ord($hash[19])&15;$value=((ord($hash[$offset])&127)<<24)|((ord($hash[$offset+1])&255)<<16)|((ord($hash[$offset+2])&255)<<8)|(ord($hash[$offset+3])&255);return str_pad((string)($value%1000000),6,'0',STR_PAD_LEFT);}
 private function decode(string $value): string {$bits='';foreach(str_split(strtoupper($value)) as $char){$pos=strpos(self::ALPHABET,$char);if($pos!==false)$bits.=str_pad(decbin($pos),5,'0',STR_PAD_LEFT);}$out='';foreach(str_split($bits,8) as $byte)if(strlen($byte)===8)$out.=chr(bindec($byte));return $out;}
}
