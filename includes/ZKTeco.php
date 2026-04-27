<?php
class ZKTeco {
    private $ip, $port, $socket;
    private $session_id = 0; private $reply_id = -1;
    const USHRT_MAX = 65535;
    public function __construct($ip, $port = 4370) { $this->ip = $ip; $this->port = $port; }
    public function connect() {
        if (!function_exists('socket_create')) return false;
        $this->socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$this->socket) return false;
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec'=>3,'usec'=>0]);
        $buf = $this->makeHeader(1000, 0, 0, '');
        @socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        $r=''; $f=''; $p=0;
        if (@socket_recvfrom($this->socket, $r, 1024, 0, $f, $p)) {
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($r, 0, 8));
            $this->session_id = hexdec($u['h6'].$u['h5']);
            return true;
        }
        return false;
    }
    public function disconnect() { if ($this->socket) @socket_close($this->socket); }
    private function chksum($p) {
        $c=0; $i=count($p); $j=1;
        while ($i>1) { $c += unpack('S', pack('C2', $p['c'.$j], $p['c'.($j+1)]))[1];
            if ($c > self::USHRT_MAX) $c -= self::USHRT_MAX; $i-=2; $j+=2; }
        if ($i) $c += $p['c'.$j];
        while ($c > self::USHRT_MAX) $c -= self::USHRT_MAX;
        $c = ~$c; while ($c < 0) $c += self::USHRT_MAX;
        return pack('S', $c);
    }
    private function makeHeader($cmd, $chk, $sid, $str) {
        $b = pack('SSSS', $cmd, $chk, $sid, $this->reply_id) . $str;
        $b = unpack('C'.(8+strlen($str)).'c', $b);
        $u = unpack('S', $this->chksum($b));
        if (is_array($u)) foreach($u as $v) $u = $v;
        $this->reply_id = ($this->reply_id + 1) % self::USHRT_MAX;
        return pack('SSSS', $cmd, $u, $sid, $this->reply_id) . $str;
    }
    public function getAttendance() {
        $buf = $this->makeHeader(13, 0, $this->session_id, '');
        @socket_sendto($this->socket, $buf, strlen($buf), 0, $this->ip, $this->port);
        $r=''; $f=''; $p=0; $logs=[];
        while (@socket_recvfrom($this->socket, $r, 1024, 0, $f, $p)) {
            if (strlen($r) <= 8) break;
            $d = substr($r, 8); $cnt = floor(strlen($d) / 40);
            for ($i=0; $i<$cnt; $i++) {
                $row = substr($d, $i*40, 40);
                $logs[] = ['user_id'=>trim(substr($row,2,24)),'time'=>date('Y-m-d H:i:s')];
            }
            if (strlen($r) < 1024) break;
        }
        return $logs;
    }
}