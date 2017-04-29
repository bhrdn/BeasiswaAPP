<?php
namespace BeasiswaAPP;

/**
 * BeasiswaAPP (Seputar Beasiswa)
 * @author Kelompok 2 <SMK Telkom Malang>
 * @version 0.1
 */
date_default_timezone_set('Asia/Jakarta');
require_once 'Plugins/vendor/autoload.php';
require_once 'Plugins/zebracurl/Zebra_cURL.php';
require_once 'Plugins/scrapper/simple_html_dom.php';
use phpFastCache\CacheManager as BeasiswaAPP_Cache;
use PHPMailer as BeasiswaAPP_Mail;
use Zebra_cURL as BeasiswaAPP_Curl;
use \PDO as BeasiswaAPP_PDO;

class BeasiswaDB
{
    protected $koneksi, $db_host = 'localhost', $db_user = 'virushcode', $db_passwd = 'seputarbeablog', $database = 'beasiswa_db';

    public function __construct()
    {
        try {
            $this->koneksi = new BeasiswaAPP_PDO("mysql:host={$this->db_host};dbname={$this->database};charset=utf8", $this->db_user, $this->db_passwd);
            $this->koneksi->setAttribute(BeasiswaAPP_PDO::ATTR_ERRMODE, BeasiswaAPP_PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }
}

class BeasiswaCommand extends BeasiswaDB
{
    private $query, $data;
    public function __construct()
    {
        parent::__construct();
    }

    public static function build(BeasiswaAPP $data)
    {
        $obj        = new self();
        $obj->query = $data->query();
        $obj->data  = $data->data();
        return $obj;
    }

    public function execute($a=false)
    {
        if ($a == "debug") {
            return [
                'query'  => $this->query,
                'data'   => $this->data,
            ];
        } else {
            if (!$this->query) {
                return false;
            } else {
                $data = $this->koneksi->prepare($this->query);
                if (preg_match("/SELECT [*]/", $this->query)) {
                    $data->execute();
                } elseif (preg_match("/SELECT/", $this->query) and $this->data) {
                    $data->execute($this->data);
                } else {
                    $data->execute($this->data);
                }
                
                if (preg_match("/COUNT/", $this->query) && preg_match("/AS/", $this->query)) {
                    return $data->fetchAll(BeasiswaAPP_PDO::FETCH_ASSOC);
                } elseif (preg_match("/COUNT/", $this->query)) {
                    return array($data->fetchColumn());
                } elseif (preg_match("/SELECT/", $this->query)) {
                    return $data->fetchAll(BeasiswaAPP_PDO::FETCH_ASSOC);
                } else {
                    return $data->rowCount();
                }
            }
        }
    }
}

abstract class BeasiswaTMP
{
    protected $command = false,
    $where             = false,
    $act               = false,
    $query             = false,
    $db_act            = false,
    $db_tables         = false,
    $count_tables      = false,
    $db_columns        = false,
    $count_columns     = false,
    $db_values         = false,
    $count_values      = false,
    $data_values       = false;
    private $tmpdat, $customquery;

    private function clear()
    {
        $this->db_act      = false;
        $this->db_tables   = false;
        $this->db_columns  = false;
        $this->db_values   = false;
        $this->where       = false;
        $this->customquery = null;
        $this->data_values = null;
    }

    public function setAct($command, $where = null, $act = null)
    {
        switch ($this->command = $command) {
            case 'insert':
                $this->db_act = strtoupper($this->convert("105 110 115 101 114 116 032 105 110 116 111"));
                break;

            case 'select':
                $this->db_act = strtoupper($this->convert("115 101 108 101 099 116"));
                $this->act    = strtoupper($act);
                $this->where  = $where;
                break;

            case 'update':
                $this->db_act = strtoupper($this->convert("117 112 100 097 116 101"));
                $this->where  = $where;
                break;

            case 'delete':
                $this->db_act = strtoupper($this->convert("100 101 108 101 116 101"));
                $this->where  = $where;
                break;

            default:
                $this->db_act = false;
                break;
        }
    }

    public function setTables(array $tables)
    {
        $this->tmpdat       = chr(0);
        $this->count_tables = (int) count($tables);
        foreach ($tables as $key => $value) {
            $this->tmpdat .= "`{$value}`,";
        }
        $this->db_tables = rtrim(trim($this->tmpdat), chr(44));
    }

    public function setColumns(array $columns)
    {
        $this->tmpdat = chr(0);
        if ($this->command == "select" and $columns[0] === "all") {
            $this->count_columns = (int) chr(49);
            $this->db_columns    = (string) chr(42);
        } elseif ($this->command != "select" and $columns[0] === "all") {
            $this->db_columns = false;
        } elseif ($this->command == "update") {
            $this->count_columns = (int) count($columns);
            foreach ($columns as $key => $value) {
                $this->tmpdat .= "`{$value}`=?, ";
            }
            $this->db_columns = rtrim(trim($this->tmpdat), chr(44));
            return $this->db_columns;
        } else {
            $this->count_columns = (int) count($columns);
            foreach ($columns as $key => $value) {
                $this->tmpdat .= "`{$value}`,";
            }
            $this->db_columns = rtrim(trim($this->tmpdat), chr(44));
        }
    }

    public function setValues(array $values = null)
    {
        $this->tmpdat       = chr(0);
        $this->count_values = (int) count($values);
        if (preg_match("/INSERT INTO/", $this->db_act)) {
            if ($this->count_values === $this->count_columns) {
                for ($i = 0; $i < $this->count_values; $i++) {
                    $this->tmpdat .= "?,";
                }
                $this->data_values = $values;
                $this->db_values   = rtrim(trim($this->tmpdat), chr(44) . "kontlo");
                return $this->db_values;
            } else {
                $this->db_values = false;
            }
        } elseif ($this->db_act === "SELECT") {
            for ($i = 0; $i < $this->count_values; $i++) {
                $this->tmpdat .= "?,";
            }
            $this->data_values = $values;
            $this->db_values   = rtrim(trim($this->tmpdat), chr(44));
        } elseif ($this->db_act == "UPDATE" || "DELETE") {
            $this->db_values   = true;
            $this->data_values = $values;
        }
    }

    public function setQuery(string $query)
    {
        $this->clear();
        $this->customquery = $query;
    }

    protected function tmp_query()
    {
        if (!$this->db_act) {
            if (isset($this->customquery) or !empty($this->customquery)) {
                return $this->customquery;
            } else {
                return $this->error("tmp_query", "Empty db_act || customquery");
            }
        } elseif (!$this->db_act || !$this->db_tables || !$this->db_columns || !$this->db_values) {
            return $this->error("tmp_query", "Empty db_act || db_tables || db_columns || db_values");
        } else {
            switch ($this->command) {
                case 'insert':
                    $insert = explode("INSERT", "{$this->db_act} {$this->db_tables} ({$this->db_columns}) VALUES ({$this->db_values})");
                    return "INSERT {$insert[1]}";
                    break;

                case 'select':
                    if ($this->act === "COUNT") {
                        $select = explode("SELECT", "{$this->db_act} COUNT({$this->db_columns}) FROM {$this->db_tables} WHERE `{$this->where}` = {$this->db_values}");
                        return "SELECT {$select[1]}";
                    } elseif ($this->db_columns == "*" || $this->data_values[0] == "all") {
                        if (isset($this->where)) {
                            $select = explode("SELECT", "{$this->db_act} {$this->db_columns} FROM {$this->db_tables} WHERE `{$this->where}`");
                            return "SELECT {$select[1]}";
                        } else {
                            $select = explode("SELECT", "{$this->db_act} {$this->db_columns} FROM {$this->db_tables}");
                            return "SELECT {$select[1]}";
                        }
                    } else {
                        $select = explode("SELECT", "{$this->db_act} {$this->db_columns} FROM {$this->db_tables} WHERE `{$this->where}` = {$this->db_values}");
                        return "SELECT {$select[1]}";
                    }
                    break;

                case 'update':
                    if (!isset($this->where)) {
                        return $this->error("tmp_query", "Query `where` not set !! - case:update");
                    } else {
                        $update = explode("UPDATE", "{$this->db_act} {$this->db_tables} SET {$this->db_columns} WHERE `{$this->where}` = ?");
                        $query  = "UPDATE {$update[1]}";
                        if (substr_count($query, chr(63)) == count($this->data_values)) {
                            return $query;
                        } else {
                            return $this->error("tmp_query", "Count of request data isn't same - case:update");
                        }
                    }
                    break;

                case 'delete':
                    $delete = explode("DELETE", "{$this->db_act} FROM {$this->db_tables} WHERE `{$this->where}` = ?");
                    if (count($this->data_values) == chr(49)) {
                        return "DELETE {$delete[1]}";
                    } else {
                        return $this->error("tmp_query", "Count of request data isn't same - case:delete");
                    }
                    break;
            }
        }
    }

    private function error($method, $details = null)
    {
        return "[ERR-{$method}] {$details}";
    }

    private function convert(string $data)
    {
        foreach (explode(chr(32), $data) as $key => $value) {
            $this->tmpdat .= chr($value);
        }
        return $this->tmpdat;
    }
}

class BeasiswaAPP extends BeasiswaTMP
{
    protected $query, $data;
    private $doc_root, $ch_ssl;

    public function __construct()
    {
        $this->doc_root = $_SERVER['DOCUMENT_ROOT'];
        if (!is_dir(__DIR__ . "/cache/phpfastcache")) {
            mkdir(__DIR__ . "/cache/phpfastcache", 0777, true);
            chmod(__DIR__ . "/cache/phpfastcache", 0777);
        }

        if (!is_dir(__DIR__ . "/cache/zebracache")) {
            mkdir(__DIR__ . "/cache/zebracache", 0777, true);
            chmod(__DIR__ . "/cache/zebracache", 0777);
        }

        $cacheconf = array(
            'storage' => 'sqlite',
            'path'    => __DIR__ . "/cache/phpfastcache",
        );

        BeasiswaAPP_Cache::setup($cacheconf);
    }

    public function date_indo($a, $b = "D j F Y")
    {
        $pattern = array (
            '/Mon[^day]/','/Tue[^sday]/','/Wed[^nesday]/','/Thu[^rsday]/',
            '/Fri[^day]/','/Sat[^urday]/','/Sun[^day]/','/Monday/','/Tuesday/',
            '/Wednesday/','/Thursday/','/Friday/','/Saturday/','/Sunday/',
            '/Jan[^uary]/','/Feb[^ruary]/','/Mar[^ch]/','/Apr[^il]/','/May/',
            '/Jun[^e]/','/Jul[^y]/','/Aug[^ust]/','/Sep[^tember]/','/Oct[^ober]/',
            '/Nov[^ember]/','/Dec[^ember]/','/January/','/February/','/March/',
            '/April/','/June/','/July/','/August/','/September/','/October/',
            '/November/','/December/',
        );
        $replace = array ('Senin, ','Selasa, ','Rabu, ','Kamis, ','Jumat, ','Sabtu, ','Minggu, ',
            'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu',
            'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des',
            'Januari','Februari','Maret','April','Juni','Juli','Agustus','Sepember','Oktober','November','Desember',
        );
        return preg_replace ($pattern, $replace, date_format(date_create($a), $b));
    }
    
    public function sqli($a) {
        if (preg_match("/(script)|(<)|(>)|(%3c)|(%3e)|(SELECT) |(UPDATE) |(INSERT) |(DELETE) |(GRANT) |(REVOKE)| (&lt;) |(&gt;)/i", $a) || preg_match("/\\w*((\\%27)|(\\'))((\\%6F)|o|(\\%4F))((\\%72)|r|(\\%52))/i", $a)) {
            return true;
        } else {
            return false;
        }
    }
    
    public function sizecache($a) {
        $io     = popen("/usr/bin/du -sk {$a}", 'r');
        $size   = fgets($io, 4096);
        $size   = substr($size, 0, strpos($size, "\t"));
        pclose($io); return $size;
    }
    
    public function urlseo($a)
    {
        if (!is_null($a)) {
            if (strlen($a) > 150) {
                $a = substr($a, 0, 150);
            }
        }
        return date("Y/m/d") . "/" . trim(strtolower(preg_replace("(-+)", "-", preg_replace("(\(|\~|\!|\@|\#|\$|\%|\^|\&|\*|\-|\+|\=|\{|\}|\[|\]|\||\"|\;|\:|\||\'|\<|\>|\,|\.|\?|\/|\s|\))", "-", $a))), "-");
    }

    public function getstr($a)
    {
        return str_get_html($a);
    }

    public function response($a, $b = 200)
    {
        $list = [
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            206 => 'Partial Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            413 => 'Payload Too Large',
            415 => 'Unsupported Media Type',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
        ];

        header("{$_SERVER['SERVER_PROTOCOL']} {$b} {$list[$b]}");
        header('Content-Type: application/json');
        return $a === null ? null : json_encode($a);
    }

    public function parse_input($a)
    {
        return array_filter(json_decode(file_get_contents('php://input'), true), function ($key) use ($a) {
            return in_array($key, $a);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function query(): string
    {
        return $this->query = $this->tmp_query();
    }

    public function data()
    {
        return $this->data = $this->data_values;
    }

    public function build(): BeasiswaCommand
    {
        return BeasiswaCommand::build($this);
    }

    public function curl($a, $b = "GET", $c = false, $d = false)
    {
        if (empty($a) || !isset($a)) {
            return false;
        } else {
            $uri          = parse_url($a);
            $this->ch_ssl = ($uri['scheme'] == "http") ? false : true;
            switch ($b) {
                case 'GET':
                    if (!$c) {
                        $content = @file_get_contents($a);
                        if ($content === false) {
                            return false;
                        } else {
                            return $content;
                        }
                    } else {
                        $ch = new BeasiswaAPP_Curl();
                        $ch->ssl($this->ch_ssl);
                        $ch->cache(__DIR__ . "/cache/zebracache", 3600);
                        return $ch->scrap($a, true);
                    }
                    break;

                case 'POST':
                    if (!$c) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $a);
                        curl_setopt($ch, CURLOPT_REFERER, "{$uri['scheme']}://{$uri['host']}");
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($d));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        return curl_exec($ch);
                    } else {
                        $ch = new BeasiswaAPP_Curl();
                        $ch->ssl($this->ch_ssl);
                        $ch->cache(__DIR__ . "/cache/zebracache", 3600);
                        return $ch->post($a, array('title' => 'a'));
                    }
                    break;
            }
        }
    }

    protected function CacheSet($a, $b)
    {
        return BeasiswaAPP_Cache::set($a, $b, 600);
    }

    protected function CacheGet($a)
    {
        return BeasiswaAPP_Cache::get($a);
    }

    protected function CacheExist($a)
    {
        $data = BeasiswaAPP_Cache::get($a);
        if (is_null($data)) {
            return false;
        } else {
            return true;
        }
    }
}
